using System;
using System.Collections.Specialized;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;
using System.Threading.Tasks;
using ElyctisCardService.Models;
using ElyctisCardService.Services;

namespace ElyctisCardService.Controllers;

public sealed class LocalHttpApi
{
	private sealed class RequestInfo
	{
		public string Method { get; set; }

		public string Path { get; set; }

		public NameValueCollection Query { get; set; }

		public NameValueCollection Headers { get; set; }
	}

	private readonly AppOptions _options;

	private readonly ElyctisCardReader _reader;

	private readonly FileLogger _logger;

	private TcpListener _listener;

	private CancellationTokenSource _cts;

	private Task _loop;

	public LocalHttpApi(AppOptions options, ElyctisCardReader reader, FileLogger logger)
	{
		_options = options;
		_reader = reader;
		_logger = logger;
	}

	public void Start()
	{
		Uri uri = new Uri(_options.ListenUrl);
		IPAddress localaddr = IPAddress.Parse(uri.Host);
		_listener = new TcpListener(localaddr, uri.Port);
		_listener.Start();
		_cts = new CancellationTokenSource();
		_loop = Task.Run(() => ListenAsync(_cts.Token));
	}

	public void Stop()
	{
		try
		{
			if (_cts != null)
			{
				_cts.Cancel();
			}
			if (_listener != null)
			{
				_listener.Stop();
			}
			if (_loop != null)
			{
				_loop.Wait(TimeSpan.FromSeconds(3.0));
			}
		}
		catch (Exception exception)
		{
			_logger.Error("HTTP API stop failed.", exception);
		}
	}

	private async Task ListenAsync(CancellationToken token)
	{
		while (!token.IsCancellationRequested)
		{
			try
			{
				TcpClient client = await _listener.AcceptTcpClientAsync().ConfigureAwait(continueOnCapturedContext: false);
				Task.Run(() => HandleClientAsync(client), token);
			}
			catch (ObjectDisposedException)
			{
				break;
			}
			catch (SocketException)
			{
				if (token.IsCancellationRequested)
				{
					break;
				}
				throw;
			}
			catch (Exception exception)
			{
				_logger.Error("HTTP listener loop failed.", exception);
			}
		}
	}

	private async Task HandleClientAsync(TcpClient client)
	{
		using (client)
		{
			NetworkStream stream = client.GetStream();
			RequestInfo request = await ReadRequestAsync(stream).ConfigureAwait(continueOnCapturedContext: false);
			if (request == null)
			{
				return;
			}
			if (request.Method == "OPTIONS")
			{
				await WriteJson(stream, 204, new { }).ConfigureAwait(continueOnCapturedContext: false);
				return;
			}
			if (IsAuthorized(request.Headers, request.Query))
			{
				try
				{
					if (request.Path == "/health")
					{
						await WriteJson(stream, 200, new
						{
							success = true,
							ok = true,
							status = "ok"
						}).ConfigureAwait(continueOnCapturedContext: false);
					}
					else if (request.Path == "/diagnostics")
					{
						await WriteJson(stream, 200, new
						{
							success = true,
							ok = true,
							status = "ok",
							listenUrl = _options.ListenUrl,
							readerNameContains = _options.ReaderNameContains,
							scannerPortName = _options.ScannerPortName,
							scannerMrzTimeoutMs = _options.ScannerMrzTimeoutMs,
							scannerAssemblyPath = _options.ScannerAssemblyPath,
							readTimeoutMs = _options.ReadTimeoutMs,
							readDG2Photo = _options.ReadDG2Photo
						}).ConfigureAwait(continueOnCapturedContext: false);
					}
					else if (request.Path == "/read-card")
					{
						await WriteJson(stream, 200, await _reader.ReadCardAsync(request.Query["mrz"]).ConfigureAwait(continueOnCapturedContext: false)).ConfigureAwait(continueOnCapturedContext: false);
					}
					else
					{
						await WriteJson(stream, 404, new
						{
							success = false,
							status = "not_found"
						}).ConfigureAwait(continueOnCapturedContext: false);
					}
					return;
				}
				catch (Exception ex)
				{
					_logger.Error("HTTP request failed.", ex);
					WriteJson(stream, 500, CardReadResult.Error("HTTP_ERROR", ex.Message)).Wait();
					return;
				}
			}
			await WriteJson(stream, 401, new
			{
				success = false,
				status = "unauthorized"
			}).ConfigureAwait(continueOnCapturedContext: false);
		}
	}

	private bool IsAuthorized(NameValueCollection headers, NameValueCollection query)
	{
		if (string.IsNullOrWhiteSpace(_options.ApiToken))
		{
			return true;
		}
		string text = headers["X-Elyctis-Token"];
		if (string.IsNullOrWhiteSpace(text))
		{
			text = query["token"];
		}
		return string.Equals(text, _options.ApiToken, StringComparison.Ordinal);
	}

	private async Task<RequestInfo> ReadRequestAsync(NetworkStream stream)
	{
		byte[] buffer = new byte[8192];
		int read = await stream.ReadAsync(buffer, 0, buffer.Length).ConfigureAwait(continueOnCapturedContext: false);
		if (read <= 0)
		{
			return null;
		}
		string raw = Encoding.ASCII.GetString(buffer, 0, read);
		StringReader reader = new StringReader(raw);
		string firstLine = reader.ReadLine();
		if (string.IsNullOrWhiteSpace(firstLine))
		{
			return null;
		}
		string[] first = firstLine.Split(' ');
		if (first.Length < 2)
		{
			return null;
		}
		NameValueCollection headers = new NameValueCollection(StringComparer.OrdinalIgnoreCase);
		while (true)
		{
			string value;
			string line = (value = reader.ReadLine());
			if (string.IsNullOrEmpty(value))
			{
				break;
			}
			int num = line.IndexOf(':');
			if (num > 0)
			{
				headers[line.Substring(0, num).Trim()] = line.Substring(num + 1).Trim();
			}
		}
		Uri uri = new Uri("http://127.0.0.1" + first[1]);
		return new RequestInfo
		{
			Method = first[0].ToUpperInvariant(),
			Path = uri.AbsolutePath.TrimEnd('/').ToLowerInvariant(),
			Query = ParseQuery(uri.Query),
			Headers = headers
		};
	}

	private NameValueCollection ParseQuery(string query)
	{
		NameValueCollection nameValueCollection = new NameValueCollection(StringComparer.OrdinalIgnoreCase);
		if (string.IsNullOrWhiteSpace(query))
		{
			return nameValueCollection;
		}
		string[] array = query.TrimStart('?').Split('&');
		foreach (string text in array)
		{
			if (!string.IsNullOrWhiteSpace(text))
			{
				string[] array2 = text.Split(new char[1] { '=' }, 2);
				string name = Uri.UnescapeDataString(array2[0]);
				string value = ((array2.Length > 1) ? Uri.UnescapeDataString(array2[1].Replace("+", " ")) : "");
				nameValueCollection[name] = value;
			}
		}
		return nameValueCollection;
	}

	private async Task WriteJson(NetworkStream stream, int statusCode, object body)
	{
		string json = ((statusCode == 204) ? "" : Json.Serialize(body));
		byte[] payload = Encoding.UTF8.GetBytes(json);
		string reason = Reason(statusCode);
		string headers = "HTTP/1.1 " + statusCode + " " + reason + "\r\nContent-Type: application/json; charset=utf-8\r\nContent-Length: " + payload.Length + "\r\nAccess-Control-Allow-Origin: " + _options.AllowedOrigin + "\r\nAccess-Control-Allow-Methods: GET, OPTIONS\r\nAccess-Control-Allow-Headers: X-Elyctis-Token, Content-Type\r\nCache-Control: no-store\r\nConnection: close\r\n\r\n";
		byte[] headerBytes = Encoding.ASCII.GetBytes(headers);
		await stream.WriteAsync(headerBytes, 0, headerBytes.Length).ConfigureAwait(continueOnCapturedContext: false);
		if (payload.Length > 0)
		{
			await stream.WriteAsync(payload, 0, payload.Length).ConfigureAwait(continueOnCapturedContext: false);
		}
	}

	private static string Reason(int statusCode)
	{
		return statusCode switch
		{
			200 => "OK", 
			204 => "No Content", 
			401 => "Unauthorized", 
			404 => "Not Found", 
			409 => "Conflict", 
			_ => "Internal Server Error", 
		};
	}
}
