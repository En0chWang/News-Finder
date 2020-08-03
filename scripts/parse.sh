i=1
for file in /Users/wangyinuo/Desktop/CSCI572/HW3/LATIMES/latimes/*.*
do
	echo "process file no.:$i"
	java -jar tika-app-1.24.1.jar --text $file >> parsed.txt
	i=$((i+1))
done