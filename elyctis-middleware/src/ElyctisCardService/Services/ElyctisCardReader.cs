using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading;
using System.Threading.Tasks;
using ElyMRTDDotNet;
using ElyctisCardService.Models;

namespace ElyctisCardService.Services;

public sealed class ElyctisCardReader
{
	private sealed class AccessPassword
	{
		public string Password { get; set; }

		public bool IsMrz { get; set; }

		public string SourceMrz { get; set; }
	}

	private readonly AppOptions _options;

	private readonly FileLogger _logger;

	private readonly ElyctisMrzScanner _scanner;

	private readonly SemaphoreSlim _singleRead = new SemaphoreSlim(1, 1);

	public ElyctisCardReader(AppOptions options, FileLogger logger, ElyctisMrzScanner scanner)
	{
		_options = options;
		_logger = logger;
		_scanner = scanner;
	}

	public async Task<CardReadResult> ReadCardAsync(string mrzPassword)
	{
		if (!(await _singleRead.WaitAsync(0).ConfigureAwait(continueOnCapturedContext: false)))
		{
			return CardReadResult.Error("READER_BUSY", "Une lecture est deja en cours.");
		}
		try
		{
			Task<CardReadResult> readTask = Task.Run(() => ReadCard(mrzPassword));
			Task timeoutTask = Task.Delay(_options.ReadTimeoutMs);
			if (await Task.WhenAny(readTask, timeoutTask).ConfigureAwait(continueOnCapturedContext: false) != readTask)
			{
				return CardReadResult.Error("TIMEOUT", "Timeout pendant la lecture de la carte.");
			}
			return await readTask.ConfigureAwait(continueOnCapturedContext: false);
		}
		finally
		{
			_singleRead.Release();
		}
	}

	private CardReadResult ReadCard(string mrzPassword)
	{
		global::ElyMRTDDotNet.ElyMRTDDotNet mrtd = null;
		try
		{
			mrtd = new global::ElyMRTDDotNet.ElyMRTDDotNet();
			if (_options.EnableVendorLogs)
			{
				mrtd.logInit(_options.LogDirectory);
			}
			else
			{
				mrtd.logDisable();
			}
			string text = SelectReader(mrtd);
			if (text == null)
			{
				CardReadResult partial = TryBuildMrzOnlyResult(mrzPassword, null, "Aucun lecteur PCSC disponible. Donnees lues depuis la MRZ uniquement.");
				if (partial != null)
				{
					return partial;
				}
				return CardReadResult.NoCard("Aucun lecteur Elyctis/PCSC disponible.");
			}
			if (!TryConnect(mrtd, text))
			{
				CardReadResult partial = TryBuildMrzOnlyResult(mrzPassword, text, "Puce non detectee. Donnees lues depuis la MRZ uniquement.");
				if (partial != null)
				{
					return partial;
				}
				return CardReadResult.NoCard("Aucune carte detectee dans le lecteur.");
			}
			PrepareSession(mrtd);
			if (string.IsNullOrWhiteSpace(mrzPassword))
			{
				mrzPassword = _scanner.ReadMrz();
			}
			string accessMrzSource = mrzPassword;
			if (!string.IsNullOrWhiteSpace(mrzPassword))
			{
				bool flag = false;
				int num = 0;
				List<AccessPassword> accessPasswords = PrepareAccessPasswords(mrzPassword);
				for (int i = 0; i < accessPasswords.Count; i++)
				{
					AccessPassword accessPassword = accessPasswords[i];
					num = ResolvePasswordType(accessPassword);
					flag = mrtd.establishAccessControl(accessPassword.Password, num);
					_logger.Info("Access control candidate " + (i + 1) + "/" + accessPasswords.Count + " type " + num + " result: " + flag);
					if (!flag && num == 1)
					{
						flag = mrtd.establishBAC(accessPassword.Password);
						_logger.Info("BAC fallback candidate " + (i + 1) + "/" + accessPasswords.Count + " result: " + flag);
					}
					if (flag)
					{
						accessMrzSource = FirstNonEmpty(accessPassword.SourceMrz, mrzPassword);
						break;
					}
				}
			}
			int num2 = TryReadDataGroup(() => mrtd.readDG1(), "DG1");
			if (num2 < 0)
			{
				CardReadResult partial = TryBuildMrzOnlyResult(accessMrzSource, text, "DG1 non lisible. Donnees lues depuis la MRZ uniquement.");
				if (partial != null)
				{
					return partial;
				}
				return CardReadResult.Error("ACCESS_CONTROL_REQUIRED", "Carte detectee, mais DG1 est protege. Saisir MRZ/CAN ou configurer PACE/BAC avant la lecture.");
			}
			string text2 = TryReadNin(mrtd);
			if (TryBuildDataFromMrz(accessMrzSource, out var data))
			{
				data.NationalIdentificationNumber = FirstNonEmpty(text2, ExtractNin(accessMrzSource), data.NationalIdentificationNumber);
				TryAttachPhoto(mrtd, data);
				return BuildSuccessResult(text, data, partial: false, null);
			}
			CardData cardData = new CardData();
			cardData.FirstName = FirstNonEmpty(SafeString(() => mrtd.getGivenNames()), SafeString(() => mrtd.getName()));
			cardData.LastName = FirstNonEmpty(SafeString(() => mrtd.getFamilyName()), SafeString(() => mrtd.getSurname()));
			cardData.FullName = SafeString(() => mrtd.getFullName());
			cardData.DocumentNumber = SafeString(() => mrtd.getDocNum());
			cardData.NationalIdentificationNumber = FirstNonEmpty(text2, ExtractNin(accessMrzSource), ExtractNin(mrzPassword));
			cardData.DocumentType = SafeString(() => mrtd.getDocumentType());
			cardData.Nationality = SafeString(() => mrtd.getNationality());
			cardData.NationalityIso = SafeString(() => mrtd.getNationality());
			cardData.DateOfBirth = FirstNonEmpty(SafeString(() => mrtd.getFullBirthDate()), SafeString(() => mrtd.getBirthDate()));
			cardData.Gender = SafeString(() => mrtd.getSex());
			cardData.ExpiryDate = FirstNonEmpty(SafeString(() => mrtd.getExpiryDate()), SafeString(() => mrtd.getValidityDate()));
			cardData.IssuingCountry = FirstNonEmpty(SafeString(() => mrtd.getIssuingCountry()), SafeString(() => mrtd.getIssuingState()));
			cardData.IssuingAuthority = SafeString(() => mrtd.getIssuingAuthority());
			cardData.Mrz = SafeString(() => mrtd.getMRZString());
			CardData cardData2 = cardData;
			TryAttachPhoto(mrtd, cardData2);
			if (string.IsNullOrWhiteSpace(cardData2.DocumentNumber) && string.IsNullOrWhiteSpace(cardData2.FirstName) && string.IsNullOrWhiteSpace(cardData2.LastName))
			{
				return CardReadResult.Error("READ_EMPTY", "Carte detectee, mais les donnees document ne sont pas lisibles. Certaines cartes exigent BAC/PACE avec MRZ/CAN.");
			}
			return BuildSuccessResult(text, cardData2, partial: false, null);
		}
		catch (SCardException ex)
		{
			_logger.Error("Smart card error.", ex);
			return CardReadResult.Error("SCARD_ERROR", ex.Message);
		}
		catch (Exception ex2)
		{
			_logger.Error("Card read failed.", ex2);
			return CardReadResult.Error("READ_ERROR", ex2.Message);
		}
		finally
		{
			try
			{
				if (mrtd != null)
				{
					mrtd.disconnect();
				}
			}
			catch
			{
			}
			try
			{
				if (mrtd != null)
				{
					mrtd.logEnd();
				}
			}
			catch
			{
			}
		}
	}

	private CardReadResult TryBuildMrzOnlyResult(string mrz, string reader, string warning)
	{
		string source = mrz;
		if (string.IsNullOrWhiteSpace(source))
		{
			source = _scanner.ReadMrz();
		}
		if (!TryBuildDataFromMrz(source, out CardData data))
		{
			return null;
		}
		data.NationalIdentificationNumber = FirstNonEmpty(ExtractNin(source), data.NationalIdentificationNumber);
		return BuildSuccessResult(reader, data, partial: true, warning);
	}

	private void TryAttachPhoto(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd, CardData data)
	{
		if (!_options.ReadDG2Photo || mrtd == null || data == null)
		{
			return;
		}
		int result = TryReadPhotoDataGroup(mrtd);
		if (result < 0)
		{
			return;
		}
		byte[] array = SafeBytes(() => mrtd.getPhoto());
		if (array != null && array.Length > 0)
		{
			data.PhotoBase64 = Convert.ToBase64String(array);
			data.PhotoMimeType = GuessMimeType(array);
			_logger.Info("Photo resolved from DG2 with byte length " + array.Length + ".");
		}
		else
		{
			_logger.Info("DG2 read succeeded but no photo payload was returned by SDK.");
		}
	}

	private int TryReadPhotoDataGroup(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		int result = TryReadDataGroup(() => mrtd.readDG2(), "DG2");
		if (result >= 0)
		{
			return result;
		}
		_logger.Warn("DG2 photo read failed; retrying once.");
		Thread.Sleep(500);
		return TryReadDataGroup(() => mrtd.readDG2(), "DG2 retry");
	}

	private static CardReadResult BuildSuccessResult(string reader, CardData data, bool partial, string warning)
	{
		CardReadResult cardReadResult = new CardReadResult();
		cardReadResult.Success = true;
		cardReadResult.Status = partial ? "partial" : "ok";
		cardReadResult.Partial = partial;
		cardReadResult.Warning = warning;
		cardReadResult.Message = warning;
		cardReadResult.Reader = reader;
		cardReadResult.ReadId = Guid.NewGuid().ToString("N");
		cardReadResult.Data = data;
		return cardReadResult;
	}

	private string SelectReader(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		string[] array = mrtd.ListReaders();
		if (array == null || array.Length == 0)
		{
			return null;
		}
		if (string.IsNullOrWhiteSpace(_options.ReaderNameContains))
		{
			return array[0];
		}
		return array.FirstOrDefault((string r) => r.IndexOf(_options.ReaderNameContains, StringComparison.OrdinalIgnoreCase) >= 0) ?? array[0];
	}

	private bool TryConnect(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd, string reader)
	{
		for (int i = 0; i <= _options.ConnectRetries; i++)
		{
			try
			{
				int num = mrtd.connect(reader);
				_logger.Info("connect(" + reader + ") returned " + num);
				if (num == 0 || num == 1 || num == 2)
				{
					return true;
				}
			}
			catch (Exception ex)
			{
				_logger.Warn("Connect attempt failed: " + ex.Message);
			}
			Thread.Sleep(_options.RetryDelayMs);
		}
		return false;
	}

	private void PrepareSession(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		TryCall(delegate
		{
			mrtd.setApduFormat(0u);
			return true;
		}, "setApduFormat(AUTOMATIC)");
		TryCall(() => mrtd.readEF_ATR(), "EF.ATR");
		TryCall(() => mrtd.readEF_CardAccess(), "EF.CardAccess");
		TryCall(() => mrtd.readEF_COM(), "EF.COM");
		try
		{
			_logger.Info("Applet type: " + mrtd.getAppletType());
		}
		catch (Exception ex)
		{
			_logger.Warn("getAppletType failed: " + ex.Message);
		}
	}

	private void TryCall(Func<bool> call, string name)
	{
		try
		{
			bool flag = call();
			_logger.Info(name + " returned " + flag);
		}
		catch (Exception ex)
		{
			_logger.Warn(name + " failed: " + ex.Message);
		}
	}

	private int TryReadDataGroup(Func<int> read, string name)
	{
		try
		{
			int num = read();
			_logger.Info(name + " read returned " + num);
			return num;
		}
		catch (Exception ex)
		{
			_logger.Warn(name + " read failed: " + ex.Message);
			return int.MinValue;
		}
	}

	private List<AccessPassword> PrepareAccessPasswords(string password)
	{
		List<AccessPassword> list = new List<AccessPassword>();
		HashSet<string> seen = new HashSet<string>(StringComparer.Ordinal);
		string text = (password ?? "").Trim();
		string text2 = text.Replace("\r", "").Replace("\n", "").Replace(" ", "");
		if (text2.Length > 20 && text2.IndexOf("<", StringComparison.Ordinal) >= 0)
		{
			foreach (string candidate in BuildMrzCandidates(text))
			{
				try
				{
					ElyMrzParser elyMrzParser = new ElyMrzParser();
					if (elyMrzParser.Parse(candidate))
					{
						string mrzPwd = elyMrzParser.GetMrzPwd();
						if (!string.IsNullOrWhiteSpace(mrzPwd))
						{
							AddAccessPassword(list, seen, mrzPwd.Trim(), isMrz: true, candidate);
						}
					}
				}
				catch (Exception ex)
				{
					_logger.Warn("MRZ candidate parsing failed: " + ex.Message);
				}
			}
		}
		AddAccessPassword(list, seen, text2, text2.Length > 12 || text2.IndexOf("<", StringComparison.Ordinal) >= 0, text);
		return list;
	}

	private static void AddAccessPassword(List<AccessPassword> list, HashSet<string> seen, string password, bool isMrz, string sourceMrz)
	{
		if (!string.IsNullOrWhiteSpace(password))
		{
			string text = password.Trim();
			string item = (isMrz ? "M:" : "C:") + text;
			if (seen.Add(item))
			{
				AccessPassword accessPassword = new AccessPassword();
				accessPassword.Password = text;
				accessPassword.IsMrz = isMrz;
				accessPassword.SourceMrz = sourceMrz;
				list.Add(accessPassword);
			}
		}
	}

	private static IEnumerable<string> BuildMrzCandidates(string mrz)
	{
		HashSet<string> seen = new HashSet<string>(StringComparer.Ordinal);
		foreach (string value in new string[3]
		{
			mrz,
			NormalizeMrzInput(mrz),
			(mrz ?? "").Replace("\r", "").Replace("\n", "").Replace(" ", "")
		})
		{
			if (!string.IsNullOrWhiteSpace(value))
			{
				foreach (string candidate in BuildMrzCandidateVariants(value))
				{
					if (!string.IsNullOrWhiteSpace(candidate) && seen.Add(candidate))
					{
						yield return candidate;
					}
				}
			}
		}
	}

	private static IEnumerable<string> BuildMrzCandidateVariants(string mrz)
	{
		yield return mrz;
		string normalized = NormalizeMrzInput(mrz);
		yield return normalized;
		string repaired = RepairMrzCheckDigits(normalized);
		if (!string.Equals(repaired, normalized, StringComparison.Ordinal))
		{
			yield return repaired;
		}
	}

	private bool TryBuildDataFromMrz(string mrz, out CardData data)
	{
		data = null;
		if (string.IsNullOrWhiteSpace(mrz))
		{
			return false;
		}
		try
		{
			ElyMrzParser elyMrzParser = new ElyMrzParser();
			string parsedMrz = null;
			foreach (string candidate in BuildMrzCandidates(mrz))
			{
				if (elyMrzParser.Parse(candidate))
				{
					parsedMrz = candidate;
					break;
				}
			}
			if (string.IsNullOrWhiteSpace(parsedMrz))
			{
				return false;
			}
			data = new CardData
			{
				FirstName = elyMrzParser.FirstName(),
				LastName = elyMrzParser.LastName(),
				FullName = elyMrzParser.FullName(),
				DocumentNumber = NormalizeDocumentNumber(elyMrzParser.DocumentNumber(), parsedMrz),
				NationalIdentificationNumber = ExtractNin(parsedMrz),
				DocumentType = elyMrzParser.DocumentType(),
				Nationality = elyMrzParser.NationalityName(),
				NationalityIso = elyMrzParser.NationalityIso(),
				DateOfBirth = FormatDate(elyMrzParser.DateOfBirth()),
				ExpiryDate = FormatDate(elyMrzParser.ExpiryDate()),
				Gender = elyMrzParser.Gender(),
				IssuingCountry = elyMrzParser.IssuingCountryName(),
				Mrz = parsedMrz
			};
			return !string.IsNullOrWhiteSpace(data.DocumentNumber) || !string.IsNullOrWhiteSpace(data.FirstName) || !string.IsNullOrWhiteSpace(data.LastName);
		}
		catch (Exception ex)
		{
			_logger.Warn("Build data from MRZ failed: " + ex.Message);
			return false;
		}
	}

	private static string FormatDate(DateTime? value)
	{
		if (!value.HasValue)
		{
			return null;
		}
		return value.Value.ToString("yyyy-MM-dd");
	}

	private string TryReadNin(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		try
		{
			int num = TryReadDataGroup(() => mrtd.readDG11(), "DG11");
			bool flag = IsDrivingLicenceApplet(mrtd);
			string value = FirstValidNin(SafeString(() => mrtd.getPersonalNumberDg11()), SafeString(() => mrtd.getPersonalNumber()), SafeStringMethod(mrtd, "getNationalIdentificationNumber"), SafeStringMethod(mrtd, "getNIN"), SafeStringMethod(mrtd, "getNin"));
			if (!string.IsNullOrWhiteSpace(value))
			{
				_logger.Info("NIN resolved from DG11/standard fields.");
				return value;
			}
			if (flag)
			{
				int num2 = TryReadDataGroup(() => mrtd.readDG10(), "DG10");
				value = FirstValidNin(SafeString(() => mrtd.getOptionalData()), SafeString(() => mrtd.getOptionalDetails()), SafeString(() => mrtd.getPersonalSummary()), SafeString(() => mrtd.getOtherValidTdNumbers()), SafeString(() => mrtd.getLicenceNumber()), ExtractNin(SafeBytes(() => mrtd.getDG10())));
				if (!string.IsNullOrWhiteSpace(value))
				{
					_logger.Info("NIN resolved from DG10/optional fields.");
					return value;
				}
				value = TryReadNinFromExtraDataGroups(mrtd);
				if (!string.IsNullOrWhiteSpace(value))
				{
					return value;
				}
				if (num < 0 && num2 < 0 && string.IsNullOrWhiteSpace(value))
				{
					return null;
				}
			}
			else if (num < 0)
			{
				return null;
			}
			return value;
		}
		catch (Exception ex)
		{
			_logger.Warn("NIN read failed: " + ex.Message);
			return null;
		}
	}

	private string TryReadNinFromExtraDataGroups(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		if (!IsDrivingLicenceApplet(mrtd) && !string.Equals(SafeString(() => mrtd.getDocumentType()), "D", StringComparison.OrdinalIgnoreCase))
		{
			return null;
		}
		TryReadDataGroup(() => ReadDataGroup(mrtd, 2), "DG2");
		int result = TryReadDataGroup(() => ReadDataGroup(mrtd, 3), "DG3");
		if (result < 0)
		{
			return null;
		}
		string value = ExtractNin(SafeBytes(() => GetDataGroup(mrtd, 3)));
		if (!string.IsNullOrWhiteSpace(value))
		{
			_logger.Info("NIN resolved from driving licence DG3 raw text.");
			return value;
		}
		return null;
	}

	private static bool IsDrivingLicenceApplet(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		try
		{
			return mrtd.getAppletType() == 2;
		}
		catch
		{
			return false;
		}
	}

	private static int ReadDataGroup(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd, int group)
	{
		switch (group)
		{
		case 1:
			return mrtd.readDG1();
		case 2:
			return mrtd.readDG2();
		case 3:
			return mrtd.readDG3();
		case 4:
			return mrtd.readDG4();
		case 5:
			return mrtd.readDG5();
		case 6:
			return mrtd.readDG6();
		case 7:
			return mrtd.readDG7();
		case 8:
			return mrtd.readDG8();
		case 9:
			return mrtd.readDG9();
		case 10:
			return mrtd.readDG10();
		case 11:
			return mrtd.readDG11();
		case 12:
			return mrtd.readDG12();
		case 13:
			return mrtd.readDG13();
		case 14:
			return mrtd.readDG14();
		case 15:
			return mrtd.readDG15();
		case 16:
			return mrtd.readDG16();
		case 32:
			return mrtd.readDG32();
		case 33:
			return mrtd.readDG33();
		case 34:
			return mrtd.readDG34();
		default:
			return int.MinValue;
		}
	}

	private static byte[] GetDataGroup(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd, int group)
	{
		switch (group)
		{
		case 1:
			return mrtd.getDG1();
		case 2:
			return mrtd.getDG2();
		case 3:
			return mrtd.getDG3();
		case 4:
			return mrtd.getDG4();
		case 5:
			return mrtd.getDG5();
		case 6:
			return mrtd.getDG6();
		case 7:
			return mrtd.getDG7();
		case 8:
			return mrtd.getDG8();
		case 9:
			return mrtd.getDG9();
		case 10:
			return mrtd.getDG10();
		case 11:
			return mrtd.getDG11();
		case 12:
			return mrtd.getDG12();
		case 13:
			return mrtd.getDG13();
		case 14:
			return mrtd.getDG14();
		case 15:
			return mrtd.getDG15();
		case 16:
			return mrtd.getDG16();
		case 32:
			return mrtd.getDG32();
		case 33:
			return mrtd.getDG33();
		case 34:
			return mrtd.getDG34();
		default:
			return null;
		}
	}

	private void LogNinProbe(global::ElyMRTDDotNet.ElyMRTDDotNet mrtd)
	{
		try
		{
			_logger.Info("NIN probe getPersonalNumber has valid candidate: " + HasValidNin(SafeString(() => mrtd.getPersonalNumber())));
			_logger.Info("NIN probe getPersonalNumberDg11 has valid candidate: " + HasValidNin(SafeString(() => mrtd.getPersonalNumberDg11())));
			_logger.Info("NIN probe getOptionalData has valid candidate: " + HasValidNin(SafeString(() => mrtd.getOptionalData())));
			_logger.Info("NIN probe getOptionalDetails has valid candidate: " + HasValidNin(SafeString(() => mrtd.getOptionalDetails())));
			_logger.Info("NIN probe getPersonalSummary has valid candidate: " + HasValidNin(SafeString(() => mrtd.getPersonalSummary())));
			_logger.Info("NIN probe getOtherValidTdNumbers has valid candidate: " + HasValidNin(SafeString(() => mrtd.getOtherValidTdNumbers())));
			_logger.Info("NIN probe getLicenceNumber has valid candidate: " + HasValidNin(SafeString(() => mrtd.getLicenceNumber())));
			_logger.Info("NIN probe getDG10 raw has valid candidate: " + HasValidNin(ExtractNin(SafeBytes(() => mrtd.getDG10()))));
			_logger.Info("NIN probe getDG11 raw has valid candidate: " + HasValidNin(ExtractNin(SafeBytes(() => mrtd.getDG11()))));
		}
		catch (Exception ex)
		{
			_logger.Warn("NIN probe logging failed: " + ex.Message);
		}
	}

	private static bool HasValidNin(string value)
	{
		return !string.IsNullOrWhiteSpace(NormalizeNin(value));
	}

	private static string ExtractNin(byte[] value)
	{
		if (value == null || value.Length == 0)
		{
			return null;
		}
		return FirstValidNin(Encoding.ASCII.GetString(value), Encoding.UTF8.GetString(value), Encoding.Unicode.GetString(value), Encoding.BigEndianUnicode.GetString(value));
	}

	private static string ExtractNin(string value)
	{
		if (string.IsNullOrWhiteSpace(value))
		{
			return null;
		}
		MatchCollection matchCollection = Regex.Matches(value, "\\d{18}");
		foreach (Match item in matchCollection)
		{
			if (item.Success)
			{
				return item.Value;
			}
		}
		return null;
	}

	private static string NormalizeNin(string value)
	{
		if (string.IsNullOrWhiteSpace(value))
		{
			return null;
		}
		string text = value.Trim();
		if (IsPlaceholder(text))
		{
			return null;
		}
		return ExtractNin(text);
	}

	private static string FirstValidNin(params string[] values)
	{
		foreach (string value in values)
		{
			string text = NormalizeNin(value);
			if (!string.IsNullOrWhiteSpace(text))
			{
				return text;
			}
		}
		return null;
	}

	private static bool IsPlaceholder(string value)
	{
		if (string.IsNullOrWhiteSpace(value))
		{
			return true;
		}
		string text = value.Trim();
		return string.Equals(text, "NA", StringComparison.OrdinalIgnoreCase) || string.Equals(text, "N/A", StringComparison.OrdinalIgnoreCase) || string.Equals(text, "NULL", StringComparison.OrdinalIgnoreCase) || string.Equals(text, "NONE", StringComparison.OrdinalIgnoreCase) || string.Equals(text, "-", StringComparison.OrdinalIgnoreCase);
	}

	private static string NormalizeMrzInput(string mrz)
	{
		string text = ((mrz ?? "").Replace("\r\n", "\r").Replace("\n", "\r").Trim());
		string[] array = text.Split(new char[1] { '\r' }, StringSplitOptions.RemoveEmptyEntries);
		List<string> list = new List<string>();
		string[] array2 = array;
		foreach (string text2 in array2)
		{
			string text3 = (text2 ?? string.Empty).Trim().ToUpperInvariant().Replace(' ', '<');
			if (!string.IsNullOrWhiteSpace(text3))
			{
				list.Add(text3);
			}
		}
		if (list.Count == 0)
		{
			return text;
		}
		int num;
		if (list.Count >= 3)
		{
			num = 30;
			list = list.GetRange(list.Count - 3, 3);
		}
		else
		{
			int num2 = 0;
			foreach (string item in list)
			{
				if (item.Length > num2)
				{
					num2 = item.Length;
				}
			}
			num = ((num2 >= 40) ? 44 : 36);
			if (list.Count > 2)
			{
				list = list.GetRange(list.Count - 2, 2);
			}
		}
		for (int j = 0; j < list.Count; j++)
		{
			string text4 = list[j];
			if (text4.Length < num)
			{
				text4 += new string('<', num - text4.Length);
			}
			else if (text4.Length > num)
			{
				text4 = text4.Substring(0, num);
			}
			list[j] = text4;
		}
		return string.Join("\r\n", list.ToArray());
	}

	private static string RepairMrzCheckDigits(string mrz)
	{
		if (string.IsNullOrWhiteSpace(mrz))
		{
			return mrz;
		}
		string[] lines = mrz.Replace("\r\n", "\n").Replace("\r", "\n").Split(new char[1] { '\n' }, StringSplitOptions.RemoveEmptyEntries);
		for (int i = 0; i < lines.Length; i++)
		{
			char[] chars = lines[i].ToUpperInvariant().Replace(' ', '<').ToCharArray();
			if (lines.Length >= 3 && i == 0 && chars.Length >= 15)
			{
				TryRepairCheckDigitField(chars, 5, 9, 14);
			}
			else if (lines.Length == 2 && i == 1 && chars.Length >= 10)
			{
				TryRepairCheckDigitField(chars, 0, 9, 9);
			}
			lines[i] = new string(chars);
		}
		return string.Join("\r\n", lines);
	}

	private static void TryRepairCheckDigitField(char[] chars, int start, int length, int checkIndex)
	{
		if (chars == null || chars.Length <= checkIndex || chars.Length < start + length || !char.IsDigit(chars[checkIndex]))
		{
			return;
		}
		int unknownIndex = -1;
		for (int i = start; i < start + length; i++)
		{
			if (!IsMrzFieldChar(chars[i]))
			{
				if (unknownIndex != -1)
				{
					return;
				}
				unknownIndex = i;
			}
		}
		if (unknownIndex == -1)
		{
			return;
		}
		foreach (char candidate in "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ<")
		{
			chars[unknownIndex] = candidate;
			if (ComputeMrzCheckDigit(chars, start, length) == chars[checkIndex])
			{
				return;
			}
		}
	}

	private static bool IsMrzFieldChar(char value)
	{
		return value == '<' || char.IsDigit(value) || (value >= 'A' && value <= 'Z');
	}

	private static char ComputeMrzCheckDigit(char[] chars, int start, int length)
	{
		int[] weights = new int[3] { 7, 3, 1 };
		int sum = 0;
		for (int i = 0; i < length; i++)
		{
			sum += MrzCharValue(chars[start + i]) * weights[i % 3];
		}
		return (char)('0' + sum % 10);
	}

	private static int MrzCharValue(char value)
	{
		if (value >= '0' && value <= '9')
		{
			return value - '0';
		}
		if (value >= 'A' && value <= 'Z')
		{
			return value - 'A' + 10;
		}
		return 0;
	}

	private static string NormalizeDocumentNumber(string value, string mrz)
	{
		string text = CleanDocumentNumber(value);
		string fromMrz = ExtractDocumentNumberFromMrz(mrz);
		if (!string.IsNullOrWhiteSpace(fromMrz) && (string.IsNullOrWhiteSpace(text) || HasSuspiciousDocumentNumber(value) || fromMrz.Length >= text.Length))
		{
			return fromMrz;
		}
		return text;
	}

	private static string ExtractDocumentNumberFromMrz(string mrz)
	{
		string repaired = RepairMrzCheckDigits(NormalizeMrzInput(mrz));
		string[] lines = repaired.Replace("\r\n", "\n").Replace("\r", "\n").Split(new char[1] { '\n' }, StringSplitOptions.RemoveEmptyEntries);
		if (lines.Length >= 3 && lines[0].Length >= 14)
		{
			return CleanDocumentNumber(lines[0].Substring(5, 9));
		}
		if (lines.Length == 2 && lines[1].Length >= 9)
		{
			return CleanDocumentNumber(lines[1].Substring(0, 9));
		}
		return null;
	}

	private static string CleanDocumentNumber(string value)
	{
		if (string.IsNullOrWhiteSpace(value))
		{
			return null;
		}
		string text = value.Trim().ToUpperInvariant().Replace("<", "").Replace("_", "").Replace(" ", "");
		return string.IsNullOrWhiteSpace(text) ? null : text;
	}

	private static bool HasSuspiciousDocumentNumber(string value)
	{
		return !string.IsNullOrWhiteSpace(value) && (value.IndexOf('<') >= 0 || value.IndexOf('_') >= 0 || value.IndexOf(' ') >= 0);
	}

	private int ResolvePasswordType(AccessPassword access)
	{
		if (_options.AccessControlPasswordType == 1 || _options.AccessControlPasswordType == 2)
		{
			return _options.AccessControlPasswordType;
		}
		if (!access.IsMrz)
		{
			return 2;
		}
		return 1;
	}

	private static byte[] SafeBytes(Func<byte[]> value)
	{
		try
		{
			return value();
		}
		catch
		{
			return null;
		}
	}

	private static string SafeString(Func<string> value)
	{
		try
		{
			return value();
		}
		catch
		{
			return null;
		}
	}

	private static string SafeStringMethod(object target, string methodName)
	{
		try
		{
			if (target == null)
			{
				return null;
			}
			System.Reflection.MethodInfo method = target.GetType().GetMethod(methodName, Type.EmptyTypes);
			if (method == null)
			{
				return null;
			}
			return method.Invoke(target, new object[0]) as string;
		}
		catch
		{
			return null;
		}
	}

	private static string FirstNonEmpty(params string[] values)
	{
		foreach (string text in values)
		{
			if (!string.IsNullOrWhiteSpace(text) && !IsPlaceholder(text))
			{
				return text.Trim();
			}
		}
		return null;
	}

	private static string GuessMimeType(byte[] bytes)
	{
		if (bytes.Length > 3 && bytes[0] == byte.MaxValue && bytes[1] == 216)
		{
			return "image/jpeg";
		}
		if (bytes.Length > 8 && bytes[0] == 137 && bytes[1] == 80 && bytes[2] == 78 && bytes[3] == 71)
		{
			return "image/png";
		}
		if (bytes.Length > 12 && bytes[4] == 106 && bytes[5] == 80)
		{
			return "image/jp2";
		}
		return "application/octet-stream";
	}
}
