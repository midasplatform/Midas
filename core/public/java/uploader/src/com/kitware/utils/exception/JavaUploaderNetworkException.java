package com.kitware.utils.exception;

public class JavaUploaderNetworkException extends JavaUploaderException
{
  private static final long serialVersionUID = 2738759628272790115L;

  public JavaUploaderNetworkException(String msg){
    super(msg);
  }
  
  public JavaUploaderNetworkException(Throwable cause){
    super(cause);
  }
  
  public JavaUploaderNetworkException(String msg, Throwable cause){
    super(msg, cause);
  }

}
