package com.kitware.utils.exception;

public class JavaUploaderQueryHttpServerException extends JavaUploaderNetworkException
{
  private static final long serialVersionUID = 2738759628272790115L;

  public JavaUploaderQueryHttpServerException(String msg){
    super(msg);
  }
  
  public JavaUploaderQueryHttpServerException(Throwable cause){
    super(cause);
  }
  
  public JavaUploaderQueryHttpServerException(String msg, Throwable cause){
    super(msg, cause);
  }

}
