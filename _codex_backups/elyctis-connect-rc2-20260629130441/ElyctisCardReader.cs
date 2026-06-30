using System;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using ElyMRTDDotNet;
using ElyctisCardService.Models;

namespace ElyctisCardService.Services
{
    public sealed class ElyctisCardReader
    {
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
            if (!await _singleRead.WaitAsync(0).ConfigureAwait(false))
                return CardReadResult.Error("READER_BUSY", "Une lecture est deja en cours.");

            try
            {
                var readTask = Task.Run(() => ReadCard(mrzPassword));
                var timeoutTask = Task.Delay(_options.ReadTimeoutMs);
                var completed = await Task.WhenAny(readTask, timeoutTask).ConfigureAwait(false);
                if (completed != readTask)
                    return CardReadResult.Error("TIMEOUT", "Timeout pendant la lecture de la carte.");

                return await readTask.ConfigureAwait(false);
            }
            finally
            {
                _singleRead.Release();
            }
        }

        private CardReadResult ReadCard(string mrzPassword)
        {
            ElyMRTDDotNet.ElyMRTDDotNet mrtd = null;
            try
            {
                mrtd = new ElyMRTDDotNet.ElyMRTDDotNet();
                if (_options.EnableVendorLogs)
                    mrtd.logInit(_options.LogDirectory);
                else
                    mrtd.logDisable();

                var reader = SelectReader(mrtd);
                if (reader == null)
                    return CardReadResult.NoCard("Aucun lecteur Elyctis/PCSC disponible.");

                var connected = TryConnect(mrtd, reader);
                if (!connected)
                    return CardReadResult.NoCard("Aucune carte detectee dans le lecteur.");

                PrepareSession(mrtd);

                if (string.IsNullOrWhiteSpace(mrzPassword))
                    mrzPassword = _scanner.ReadMrz();

                if (!string.IsNullOrWhiteSpace(mrzPassword))
                {
                    var access = PrepareAccessPassword(mrzPassword);
                    var passwordType = ResolvePasswordType(access);
                    var accessOk = mrtd.establishAccessControl(access.Password, passwordType);
                    _logger.Info("Access control type " + passwordType + " result: " + accessOk);
                    if (!accessOk && passwordType == 1)
                    {
                        accessOk = mrtd.establishBAC(access.Password);
                        _logger.Info("BAC fallback result: " + accessOk);
                    }
                }

                var dg1Result = TryReadDataGroup(() => mrtd.readDG1(), "DG1");
                if (dg1Result < 0)
                {
                    return CardReadResult.Error(
                        "ACCESS_CONTROL_REQUIRED",
                        "Carte detectee, mais DG1 est protege. Saisir MRZ/CAN ou configurer PACE/BAC avant la lecture.");
                }

                var nin = TryReadNin(mrtd);

                CardData parsedMrzData;
                if (TryBuildDataFromMrz(mrzPassword, out parsedMrzData))
                {
                    

                    parsedMrzData.NationalIdentificationNumber = FirstNonEmpty(nin, ExtractNin(mrzPassword));
                    return new CardReadResult
                    {
                        Success = true,
                        Status = "ok",
                        Reader = reader,
                        ReadId = Guid.NewGuid().ToString("N"),
                        Data = parsedMrzData
                    };
                }

                var dg2Result = 0;
                if (_options.ReadDG2Photo)
                    dg2Result = TryReadDataGroup(() => mrtd.readDG2(), "DG2");

                
                var data = new CardData
                {
                    FirstName = FirstNonEmpty(SafeString(() => mrtd.getGivenNames()), SafeString(() => mrtd.getName())),
                    LastName = FirstNonEmpty(SafeString(() => mrtd.getFamilyName()), SafeString(() => mrtd.getSurname())),
                    FullName = SafeString(() => mrtd.getFullName()),
                    DocumentNumber = SafeString(() => mrtd.getDocNum()),
                    NationalIdentificationNumber = FirstNonEmpty(nin, ExtractNin(mrzPassword)),
                    
                    DocumentType = SafeString(() => mrtd.getDocumentType()),
                    Nationality = SafeString(() => mrtd.getNationality()),
                    NationalityIso = SafeString(() => mrtd.getNationality()),
                    DateOfBirth = FirstNonEmpty(SafeString(() => mrtd.getFullBirthDate()), SafeString(() => mrtd.getBirthDate())),
                    Gender = SafeString(() => mrtd.getSex()),
                    ExpiryDate = FirstNonEmpty(SafeString(() => mrtd.getExpiryDate()), SafeString(() => mrtd.getValidityDate())),
                    IssuingCountry = FirstNonEmpty(SafeString(() => mrtd.getIssuingCountry()), SafeString(() => mrtd.getIssuingState())),
                    IssuingAuthority = SafeString(() => mrtd.getIssuingAuthority()),
                    Mrz = SafeString(() => mrtd.getMRZString())
                };

                var photo = dg2Result >= 0 ? SafeBytes(() => mrtd.getPhoto()) : null;
                if (photo != null && photo.Length > 0)
                {
                    data.PhotoBase64 = Convert.ToBase64String(photo);
                    data.PhotoMimeType = GuessMimeType(photo);
                }

                if (string.IsNullOrWhiteSpace(data.DocumentNumber) &&
                    string.IsNullOrWhiteSpace(data.FirstName) &&
                    string.IsNullOrWhiteSpace(data.LastName))
                {
                    return CardReadResult.Error(
                        "READ_EMPTY",
                        "Carte detectee, mais les donnees document ne sont pas lisibles. Certaines cartes exigent BAC/PACE avec MRZ/CAN.");
                }

                return new CardReadResult
                {
                    Success = true,
                    Status = "ok",
                    Reader = reader,
                    ReadId = Guid.NewGuid().ToString("N"),
                    Data = data
                };
            }
            catch (ElyMRTDDotNet.SCardException ex)
            {
                _logger.Error("Smart card error.", ex);
                return CardReadResult.Error("SCARD_ERROR", ex.Message);
            }
            catch (Exception ex)
            {
                _logger.Error("Card read failed.", ex);
                return CardReadResult.Error("READ_ERROR", ex.Message);
            }
            finally
            {
                try { if (mrtd != null) mrtd.disconnect(); } catch { }
                try { if (mrtd != null) mrtd.logEnd(); } catch { }
            }
        }

        private string SelectReader(ElyMRTDDotNet.ElyMRTDDotNet mrtd)
        {
            var readers = mrtd.ListReaders();
            if (readers == null || readers.Length == 0)
                return null;

            if (string.IsNullOrWhiteSpace(_options.ReaderNameContains))
                return readers[0];

            return readers.FirstOrDefault(r => r.IndexOf(_options.ReaderNameContains, StringComparison.OrdinalIgnoreCase) >= 0)
                   ?? readers[0];
        }

        private bool TryConnect(ElyMRTDDotNet.ElyMRTDDotNet mrtd, string reader)
        {
            for (var attempt = 0; attempt <= _options.ConnectRetries; attempt++)
            {
                try
                {
                    var rc = mrtd.connect(reader);
                    _logger.Info("connect(" + reader + ") returned " + rc);
                    if (rc == 0 || rc == 1)
                        return true;
                }
                catch (Exception ex)
                {
                    _logger.Warn("Connect attempt failed: " + ex.Message);
                }

                Thread.Sleep(_options.RetryDelayMs);
            }

            return false;
        }

        private void PrepareSession(ElyMRTDDotNet.ElyMRTDDotNet mrtd)
        {
            TryCall(() =>
            {
                mrtd.setApduFormat(0, 0);
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
                var ok = call();
                _logger.Info(name + " returned " + ok);
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
                var rc = read();
                _logger.Info(name + " read returned " + rc);
                return rc;
            }
            catch (Exception ex)
            {
                _logger.Warn(name + " read failed: " + ex.Message);
                return int.MinValue;
            }
        }

        private AccessPassword PrepareAccessPassword(string password)
        {
            var value = (password ?? "").Trim();
            var compact = value.Replace("\r", "").Replace("\n", "").Replace(" ", "");

            if (compact.Length > 20 && compact.IndexOf("<", StringComparison.Ordinal) >= 0)
            {
                try
                {
                    var parser = new ElyMrzParser();
                    if (parser.Parse(value) || parser.Parse(compact))
                    {
                        var mrzPassword = parser.GetMrzPwd();
                        if (!string.IsNullOrWhiteSpace(mrzPassword))
                            return new AccessPassword { Password = mrzPassword.Trim(), IsMrz = true };
                    }
                }
                catch (Exception ex)
                {
                    _logger.Warn("MRZ parsing failed: " + ex.Message);
                }
            }

            return new AccessPassword
            {
                Password = compact,
                IsMrz = compact.Length > 12 || compact.IndexOf("<", StringComparison.Ordinal) >= 0
            };
        }

        private bool TryBuildDataFromMrz(string mrz, out CardData data)
        {
            data = null;
            if (string.IsNullOrWhiteSpace(mrz))
                return false;

            try
            {
                var parser = new ElyMrzParser();
                var compact = mrz.Replace("\r", "").Replace("\n", "").Replace(" ", "");
                if (!parser.Parse(mrz) && !parser.Parse(compact))
                    return false;

                data = new CardData
                {
                    FirstName = parser.FirstName(),
                    LastName = parser.LastName(),
                    FullName = parser.FullName(),
                    DocumentNumber = parser.DocumentNumber(),
                    NationalIdentificationNumber = ExtractNin(mrz),
                    DocumentType = parser.DocumentType(),
                    Nationality = parser.NationalityName(),
                    NationalityIso = parser.NationalityIso(),
                    DateOfBirth = FormatDate(parser.DateOfBirth()),
                    ExpiryDate = FormatDate(parser.ExpiryDate()),
                    Gender = parser.Gender(),
                    IssuingCountry = parser.IssuingCountryName(),
                    Mrz = mrz
                };

                return !string.IsNullOrWhiteSpace(data.DocumentNumber) ||
                       !string.IsNullOrWhiteSpace(data.FirstName) ||
                       !string.IsNullOrWhiteSpace(data.LastName);
            }
            catch (Exception ex)
            {
                _logger.Warn("Build data from MRZ failed: " + ex.Message);
                return false;
            }
        }

        private static string FormatDate(DateTime? value)
        {
            return value.HasValue ? value.Value.ToString("yyyy-MM-dd") : null;
        }

        private string TryReadNin(ElyMRTDDotNet.ElyMRTDDotNet mrtd)
        {
            try
            {
                var dg11 = TryReadDataGroup(() => mrtd.readDG11(), "DG11");
                if (dg11 < 0)
                    return null;

                var personalNumber = SafeString(() => mrtd.getPersonalNumberDg11());
                return NormalizeNin(personalNumber);
            }
            catch (Exception ex)
            {
                _logger.Warn("NIN read failed: " + ex.Message);
                return null;
            }
        }

        private static string ExtractNin(string value)
        {
            if (string.IsNullOrWhiteSpace(value))
                return null;

            var matches = System.Text.RegularExpressions.Regex.Matches(value, @"\d{18}");
            foreach (System.Text.RegularExpressions.Match match in matches)
            {
                if (match.Success)
                    return match.Value;
            }

            return null;
        }

        private static string NormalizeNin(string value)
        {
            if (string.IsNullOrWhiteSpace(value))
                return null;

            return ExtractNin(value) ?? value.Trim();
        }

        private int ResolvePasswordType(AccessPassword access)
        {
            if (_options.AccessControlPasswordType == 1 || _options.AccessControlPasswordType == 2)
                return _options.AccessControlPasswordType;

            return access.IsMrz ? 1 : 2;
        }

        private static byte[] SafeBytes(Func<byte[]> value)
        {
            try { return value(); } catch { return null; }
        }

        private static string SafeString(Func<string> value)
        {
            try { return value(); } catch { return null; }
        }

        private static string FirstNonEmpty(params string[] values)
        {
            foreach (var value in values)
            {
                if (!string.IsNullOrWhiteSpace(value))
                    return value.Trim();
            }

            return null;
        }

        private static string GuessMimeType(byte[] bytes)
        {
            if (bytes.Length > 3 && bytes[0] == 0xFF && bytes[1] == 0xD8)
                return "image/jpeg";
            if (bytes.Length > 8 && bytes[0] == 0x89 && bytes[1] == 0x50 && bytes[2] == 0x4E && bytes[3] == 0x47)
                return "image/png";
            if (bytes.Length > 12 && bytes[4] == 0x6A && bytes[5] == 0x50)
                return "image/jp2";
            return "application/octet-stream";
        }

        private sealed class AccessPassword
        {
            public string Password { get; set; }
            public bool IsMrz { get; set; }
        }
    }
}
