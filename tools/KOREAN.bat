echo "KOREAN BAT SCRIPT"

# Copy over the language specific schema
cp ../data/schema_Korean.json ./SaintCoinach.Cmd/ex.json

# move to directory
cd SaintCoinach.Cmd

# set language and path
SaintCoinach.Cmd.exe "D:\FFXIV\Korean\Game\FFXIV" "lang ko" "setpath D:\FFXIV\Korean\Game\FFXIV"

# extract all raw exd
SaintCoinach.Cmd.exe "D:\FFXIV\Korean\Game\FFXIV" "lang ko" "allrawexd"

# restore schema file
cp ../data/schema_Official.json ./SaintCoinach.Cmd/ex.json

# Finished
pause
