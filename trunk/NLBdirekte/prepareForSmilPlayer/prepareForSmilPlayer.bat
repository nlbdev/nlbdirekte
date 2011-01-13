@echo off

echo %time%

cd %~dp0

tidy.exe %1\ncc.html > ncc.xhtml
AltovaXML.exe /in ncc.xhtml /xslt2 JsonML.xslt /out ncc.json
del ncc.xhtml

for /f "usebackq delims=|" %%f in (`dir /b "%1" ^| findstr /i /e .smil ^| findstr /i /x /v master.smil`) do (
AltovaXML.exe /in %1\%%f /param "filename"="%%f" /xslt2 stripSmil.xslt /out %%f_temp.xml
AltovaXML.exe /in %%f_temp.xml /xslt2 JsonML.xslt /out %%f.json
del %%f_temp.xml
)

python prepareForSmilPlayer.py

del ncc.json
for /f "usebackq delims=|" %%f in (`dir /b "%1" ^| findstr /i /e .smil ^| findstr /i /x /v master.smil`) do (
del %%f.json
)

move smil.json %1
move toc.json %1
move pagelist.json %1
move metadata.json %1

echo %time%

pause