package com.kitware.utils.exception;

public class JavaUploaderException extends Exception
{

  private static final long serialVersionUID = -459018097302616445L;
  
  protected String emsg = null;
  
  public String getMessage(){
    return emsg; 
  }
  
  public JavaUploaderException(String emsg) {
    super();
    this.emsg = emsg;      
  }
  
  public JavaUploaderException(String emsg, Throwable cause) {
    super(cause);
    this.emsg = emsg;      
  }
  
  public JavaUploaderException(Throwable cause) {
    super(cause);
    this.emsg = cause.getMessage();      
  }
}
