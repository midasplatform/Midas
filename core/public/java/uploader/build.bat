@echo off
path = %PATH%;C:\Program Files\Java\jdk1.6.0_23\bin
mkdir dist
mkdir bin
javac -g  -d bin -verbose -source 5 -target 6 src/com/kitware/utils/*.java src/com/kitware/utils/exception/*.java -cp "C:\Program Files\Java\jdk1.6.0_23\jre\lib\plugin.jar;"
cd bin
jar cvfm ../dist/MidasUploader.jar ../src/manifest com
cd ..
cd dist
jarsigner -keystore ../kitware!.keystore MidasUploader.jar mycert

cd ..
