import requests

contest = "jan23results"
url = "http://usaco.org/index.php?page=" + contest;
r = requests.get(url);
lines = r.text.split("\n");

contestName = "2023 Jan"

lineno = 96;

# INSERT INTO problems (contest,name,url,type) VALUES (?,?,?,?);
for i in range(3,-1,-1):
	# type = i
	for j in range(3):
		name = (lines[lineno + 7 * i + j * 2 + 1].split("<b>")[-1].split("</b>")[0]);
		url = ("http://usaco.org/" + lines[lineno + 7 * i + j * 2 + 2].split("'>")[0][15:]);
		print("INSERT INTO problems (contest,name,url,type) VALUES (\"{a}\",\"{b}\",\"{c}\",{d});".format(a=contestName,b=name,c=url,d=i));
	print()
