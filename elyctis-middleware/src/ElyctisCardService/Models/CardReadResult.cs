namespace ElyctisCardService.Models;

public sealed class CardReadResult
{
	public bool Success { get; set; }

	public string Status { get; set; }

	public string ErrorCode { get; set; }

	public string Message { get; set; }

	public bool Partial { get; set; }

	public string Warning { get; set; }

	public string Reader { get; set; }

	public string ReadId { get; set; }

	public CardData Data { get; set; }

	public static CardReadResult NoCard(string message)
	{
		CardReadResult cardReadResult = new CardReadResult();
		cardReadResult.Success = false;
		cardReadResult.Status = "no_card";
		cardReadResult.ErrorCode = "NO_CARD";
		cardReadResult.Message = message;
		return cardReadResult;
	}

	public static CardReadResult Error(string code, string message)
	{
		CardReadResult cardReadResult = new CardReadResult();
		cardReadResult.Success = false;
		cardReadResult.Status = "error";
		cardReadResult.ErrorCode = code;
		cardReadResult.Message = message;
		return cardReadResult;
	}
}
