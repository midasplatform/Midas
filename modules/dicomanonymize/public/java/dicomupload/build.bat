@echo off
set java_dir=C:\Program Files\Java\jdk1.6.0_23
mkdir dist
mkdir bin
javac -g  -d bin -verbose -source 5 -target 6 src/com/kitware/utils/*.java src/com/kitware/utils/exception/*.java src/org/json/*.java -cp "%java_dir%\jre\lib\plugin.jar;%CD%\lib\CTP.jar"
cd bin
jar cvfm ../dist/DicomUploader.jar ../src/manifest com org
cd ..
cd dist
jarsigner -keystore ../kitware!.keystore DicomUploader.jar mycert
cd ..
