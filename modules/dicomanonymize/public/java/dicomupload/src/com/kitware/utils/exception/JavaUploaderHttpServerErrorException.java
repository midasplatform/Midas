package com.kitware.utils.exception;

public class JavaUploaderHttpServerErrorException extends JavaUploaderNetworkException
{
  private static final long serialVersionUID = 2738759628272790115L;

  public JavaUploaderHttpServerErrorException(String msg){
    super(msg);
  }
  
  public JavaUploaderHttpServerErrorException(Throwable cause){
    super(cause);
  }
  
  public JavaUploaderHttpServerErrorException(String msg, Throwable cause){
    super(msg, cause);
  }

}
