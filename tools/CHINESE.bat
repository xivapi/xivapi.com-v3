echo "CHINESE BAT SCRIPT"

# Copy over the language specific schema
cp ../data/schema_Chinese.json ./SaintCoinach.Cmd/ex.json

# move to directory
cd SaintCoinach.Cmd

# set language and path
SaintCoinach.Cmd.exe "D:\FFXIV\Chinese\Game\FFXIV" "lang chs" "setpath D:\FFXIV\Chinese\Game\FFXIV"

# extract all raw exd
SaintCoinach.Cmd.exe "D:\FFXIV\Chinese\Game\FFXIV" "lang chs" "allrawexd"

# restore schema file
cp ../data/schema_Official.json ./SaintCoinach.Cmd/ex.json

# Finished
pause
