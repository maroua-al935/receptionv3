using System.IO;
using System.Web.Script.Serialization;

namespace ElyctisCardService.Models;

public sealed class AppOptions
{
	public string ListenUrl { get; set; }

	public string AllowedOrigin { get; set; }

	public string ApiToken { get; set; }

	public string ReaderNameContains { get; set; }

	public int ReadTimeoutMs { get; set; }

	public int ConnectRetries { get; set; }

	public int RetryDelayMs { get; set; }

	public bool ReadDG2Photo { get; set; }

	public bool EnableVendorLogs { get; set; }

	public string LogDirectory { get; set; }

	public int AccessControlPasswordType { get; set; }

	public bool AutoReadMrzFromScanner { get; set; }

	public string ScannerPortName { get; set; }

	public int ScannerMrzTimeoutMs { get; set; }

	public string ScannerAssemblyPath { get; set; }

	public AppOptions()
	{
		ListenUrl = "http://10.16.40.3:8765/";
		AllowedOrigin = "http://127.0.0.1:8008";
		ApiToken = "change-this-local-token";
		ReaderNameContains = "";
		ReadTimeoutMs = 120000;
		ConnectRetries = 2;
		RetryDelayMs = 500;
		ReadDG2Photo = true;
		EnableVendorLogs = true;
		LogDirectory = "logs";
		AccessControlPasswordType = 0;
		AutoReadMrzFromScanner = true;
		ScannerPortName = "COM6";
		ScannerMrzTimeoutMs = 20000;
		ScannerAssemblyPath = "C:\\\\Users\\\\ANAM1429\\\\Desktop\\\\Application - ELY TRAVEL DOC v4.9.3\\\\x64\\\\Release\\\\ELY TRAVEL DOC.exe";
	}

	public static AppOptions Load(string path)
	{
		if (!File.Exists(path))
		{
			return new AppOptions();
		}
		string input = File.ReadAllText(path);
		return new JavaScriptSerializer().Deserialize<AppOptions>(input) ?? new AppOptions();
	}
}
