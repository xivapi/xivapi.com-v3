echo "OFFICIAL BAT SCRIPT"

# Copy over the language specific schema
cp ../data/schema_Chinese.json ./SaintCoinach.Cmd/ex.json

# move to directory
cd SaintCoinach.Cmd

# set language and path
SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" "setpath C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn"

# extract all raw exd
SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" "allrawexd"

# restore schema file
cp ../data/schema_Official.json ./SaintCoinach.Cmd/ex.json

# Finished
pause
