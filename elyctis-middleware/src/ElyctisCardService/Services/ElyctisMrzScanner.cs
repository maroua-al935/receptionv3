using System;
using System.Collections.Generic;
using System.IO;
using System.IO.Ports;
using System.Linq;
using System.Management;
using System.Reflection;
using System.Threading;
using ElyctisCardService.Models;

namespace ElyctisCardService.Services;

public sealed class ElyctisMrzScanner
{
	private sealed class ScannerPortCandidate
	{
		public string PortName { get; set; }

		public bool Preferred { get; set; }

		public bool SystemPort { get; set; }
	}

	private sealed class MrzCallback
	{
		private readonly ManualResetEventSlim _received = new ManualResetEventSlim(initialState: false);

		private string _mrz;

		public void OnMrz(string mrz)
		{
			_mrz = mrz;
			_received.Set();
		}

		public string Wait(int timeoutMs)
		{
			_received.Wait(timeoutMs);
			return _mrz;
		}
	}

	private readonly AppOptions _options;

	private readonly FileLogger _logger;

	public ElyctisMrzScanner(AppOptions options, FileLogger logger)
	{
		_options = options;
		_logger = logger;
	}

	public string ReadMrz()
	{
		if (!_options.AutoReadMrzFromScanner)
		{
			return null;
		}
		string scannerAssemblyPath = ResolveScannerAssemblyPath();
		if (string.IsNullOrWhiteSpace(scannerAssemblyPath) || !File.Exists(scannerAssemblyPath))
		{
			_logger.Warn("Scanner assembly not found: " + _options.ScannerAssemblyPath);
			return null;
		}
		List<ScannerPortCandidate> candidates = BuildPortCandidates();
		if (candidates.Count == 0)
		{
			_logger.Warn("No scanner COM ports were found.");
			return null;
		}
		for (int i = 0; i < candidates.Count; i++)
		{
			ScannerPortCandidate candidate = candidates[i];
			int timeoutMs = ResolveTimeout(candidate, i);
			string text = ReadMrzFromPort(scannerAssemblyPath, candidate.PortName, timeoutMs);
			if (!string.IsNullOrWhiteSpace(text))
			{
				if (!string.Equals(_options.ScannerPortName, candidate.PortName, StringComparison.OrdinalIgnoreCase))
				{
					_logger.Info("MRZ scanner working port resolved as " + candidate.PortName + " instead of configured " + _options.ScannerPortName);
				}
				return text.Trim();
			}
		}
		_logger.Info("MRZ scanner returned no data on any candidate port.");
		return null;
	}

	private string ReadMrzFromPort(string scannerAssemblyPath, string portName, int timeoutMs)
	{
		object obj = null;
		Type type = null;
		string originalDirectory = null;
		try
		{
			string scannerDirectory = Path.GetDirectoryName(scannerAssemblyPath);
			originalDirectory = Directory.GetCurrentDirectory();
			if (!string.IsNullOrWhiteSpace(scannerDirectory) && Directory.Exists(scannerDirectory))
			{
				Directory.CreateDirectory(Path.Combine(scannerDirectory, "logs"));
				Directory.SetCurrentDirectory(scannerDirectory);
			}
			Assembly assembly = Assembly.LoadFrom(scannerAssemblyPath);
			type = assembly.GetType("ELY_TRAVEL_DOC.Scanner", throwOnError: true);
			MrzCallback mrzCallback = new MrzCallback();
			Type type2 = assembly.GetType("ELY_TRAVEL_DOC.DelegateReadMrz", throwOnError: true);
			Delegate obj2 = Delegate.CreateDelegate(type2, mrzCallback, "OnMrz");
			obj = Activator.CreateInstance(type, obj2);
			_logger.Info("MRZ scanner trying " + portName + " with timeout " + timeoutMs + " ms.");
			bool flag = (bool)type.GetMethod("Connect").Invoke(obj, new object[1] { portName });
			_logger.Info("MRZ scanner connect(" + portName + ") returned " + flag);
			if (!flag)
			{
				return null;
			}
			type.GetMethod("Inquire").Invoke(obj, new object[0]);
			string text = mrzCallback.Wait(timeoutMs);
			if (string.IsNullOrWhiteSpace(text))
			{
				MethodInfo method = type.GetMethod("ReadMRZ", BindingFlags.Instance | BindingFlags.Public | BindingFlags.NonPublic);
				if (method != null)
				{
					text = method.Invoke(obj, new object[1] { timeoutMs }) as string;
				}
			}
			if (string.IsNullOrWhiteSpace(text))
			{
				_logger.Info("MRZ scanner returned no data on " + portName + ".");
				return null;
			}
			_logger.Info("MRZ scanner returned data length " + text.Length + " on " + portName + ".");
			return text.Trim();
		}
		catch (Exception ex)
		{
			_logger.Warn("MRZ scanner read failed on " + portName + ": " + Unwrap(ex).Message);
			return null;
		}
		finally
		{
			try
			{
				if (obj != null && type != null)
				{
					type.GetMethod("Disconnect").Invoke(obj, new object[0]);
				}
			}
			catch
			{
			}
			try
			{
				if (!string.IsNullOrWhiteSpace(originalDirectory) && Directory.Exists(originalDirectory))
				{
					Directory.SetCurrentDirectory(originalDirectory);
				}
			}
			catch
			{
			}
		}
	}

	private string ResolveScannerAssemblyPath()
	{
		if (!string.IsNullOrWhiteSpace(_options.ScannerAssemblyPath) && File.Exists(_options.ScannerAssemblyPath))
		{
			return _options.ScannerAssemblyPath;
		}
		string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
		string[] candidates =
		{
			Path.Combine(baseDirectory, "Scanner", "x86", "ELY TRAVEL DOC.exe"),
			Path.Combine(baseDirectory, "ELY_TRAVEL_DOC_x86", "ELY TRAVEL DOC.exe"),
			Path.Combine(baseDirectory, "ELY TRAVEL DOC.exe")
		};
		return candidates.FirstOrDefault(File.Exists);
	}

	private List<ScannerPortCandidate> BuildPortCandidates()
	{
		List<ScannerPortCandidate> candidates = new List<ScannerPortCandidate>();
		HashSet<string> seen = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
		List<ScannerPortCandidate> pnpCandidates = DiscoverPortsFromPnp().ToList();
		HashSet<string> systemPorts = new HashSet<string>(pnpCandidates.Where((ScannerPortCandidate c) => c.SystemPort).Select((ScannerPortCandidate c) => c.PortName), StringComparer.OrdinalIgnoreCase);
		foreach (ScannerPortCandidate candidate in pnpCandidates.Where((ScannerPortCandidate c) => c.Preferred))
		{
			AddPort(candidates, seen, candidate.PortName, preferred: true, systemPort: false);
		}
		string configuredPort = NormalizePortName(_options.ScannerPortName);
		if (!string.IsNullOrWhiteSpace(configuredPort))
		{
			ScannerPortCandidate configuredProfile = pnpCandidates.FirstOrDefault((ScannerPortCandidate c) => string.Equals(c.PortName, configuredPort, StringComparison.OrdinalIgnoreCase));
			bool configuredIsSystem = configuredProfile != null && configuredProfile.SystemPort;
			bool configuredIsPreferred = configuredProfile != null && configuredProfile.Preferred;
			bool noDetectedElyctisPort = !pnpCandidates.Any((ScannerPortCandidate c) => c.Preferred);
			AddPort(candidates, seen, configuredPort, configuredIsPreferred || (!configuredIsSystem && noDetectedElyctisPort), configuredIsSystem);
		}
		foreach (ScannerPortCandidate candidate2 in pnpCandidates.Where((ScannerPortCandidate c) => !c.Preferred && !c.SystemPort))
		{
			AddPort(candidates, seen, candidate2.PortName, preferred: false, systemPort: false);
		}
		foreach (string portName in DiscoverSerialPorts())
		{
			if (!systemPorts.Contains(NormalizePortName(portName)))
			{
				AddPort(candidates, seen, portName, preferred: false, systemPort: false);
			}
		}
		foreach (ScannerPortCandidate candidate3 in pnpCandidates.Where((ScannerPortCandidate c) => c.SystemPort))
		{
			AddPort(candidates, seen, candidate3.PortName, preferred: false, systemPort: true);
		}
		if (candidates.Count == 0)
		{
			AddPort(candidates, seen, "COM6", preferred: true, systemPort: false);
		}
		return candidates;
	}

	private static void AddPort(List<ScannerPortCandidate> candidates, HashSet<string> seen, string portName, bool preferred, bool systemPort)
	{
		string text = NormalizePortName(portName);
		if (string.IsNullOrWhiteSpace(text) || !seen.Add(text))
		{
			return;
		}
		candidates.Add(new ScannerPortCandidate
		{
			PortName = text,
			Preferred = preferred,
			SystemPort = systemPort
		});
	}

	private IEnumerable<ScannerPortCandidate> DiscoverPortsFromPnp()
	{
		List<ScannerPortCandidate> candidates = new List<ScannerPortCandidate>();
		try
		{
			using ManagementObjectSearcher searcher = new ManagementObjectSearcher("SELECT Name, DeviceID, PNPDeviceID, Manufacturer FROM Win32_PnPEntity");
			foreach (ManagementObject item in searcher.Get())
			{
				string identity = string.Join(" ", new[]
				{
					SafeManagementString(item, "Name"),
					SafeManagementString(item, "DeviceID"),
					SafeManagementString(item, "PNPDeviceID"),
					SafeManagementString(item, "Manufacturer")
				});
				string portName = ExtractPortName(identity);
				if (string.IsNullOrWhiteSpace(portName))
				{
					continue;
				}
				bool preferred = IsLikelyElyctisPort(identity);
				bool systemPort = identity.IndexOf("Intel", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("AMT", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("Bluetooth", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("Modem", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("Management", StringComparison.OrdinalIgnoreCase) >= 0;
				candidates.Add(new ScannerPortCandidate
				{
					PortName = portName,
					Preferred = preferred,
					SystemPort = systemPort
				});
			}
		}
		catch (Exception ex)
		{
			_logger.Warn("COM port discovery from Windows PnP failed: " + ex.Message);
		}
		return candidates;
	}

	private static IEnumerable<string> DiscoverSerialPorts()
	{
		try
		{
			return SerialPort.GetPortNames();
		}
		catch
		{
			return Array.Empty<string>();
		}
	}

	private int ResolveTimeout(ScannerPortCandidate candidate, int index)
	{
		int configured = Math.Max(3000, _options.ScannerMrzTimeoutMs);
		if (candidate.SystemPort)
		{
			return Math.Min(configured, 3000);
		}
		if (candidate.Preferred)
		{
			return configured;
		}
		return Math.Min(configured, 5000);
	}

	private static string SafeManagementString(ManagementBaseObject item, string name)
	{
		try
		{
			object value = item[name];
			return value == null ? string.Empty : Convert.ToString(value);
		}
		catch
		{
			return string.Empty;
		}
	}

	private static bool IsLikelyElyctisPort(string identity)
	{
		return identity.IndexOf("ELYCTIS", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("Virtual Com", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("VID_2B78", StringComparison.OrdinalIgnoreCase) >= 0 || identity.IndexOf("PID_0005", StringComparison.OrdinalIgnoreCase) >= 0;
	}

	private static string ExtractPortName(string value)
	{
		if (string.IsNullOrWhiteSpace(value))
		{
			return null;
		}
		System.Text.RegularExpressions.Match match = System.Text.RegularExpressions.Regex.Match(value, "\\bCOM\\d+\\b", System.Text.RegularExpressions.RegexOptions.IgnoreCase);
		return match.Success ? NormalizePortName(match.Value) : null;
	}

	private static string NormalizePortName(string portName)
	{
		if (string.IsNullOrWhiteSpace(portName))
		{
			return null;
		}
		return portName.Trim().ToUpperInvariant();
	}


	private static Exception Unwrap(Exception ex)
	{
		if (!(ex is TargetInvocationException { InnerException: not null } ex2))
		{
			return ex;
		}
		return ex2.InnerException;
	}
}
