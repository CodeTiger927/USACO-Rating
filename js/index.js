var data = null;
var sortMode = 0;
var curD = 0;
var useMedian = false;

$.post("./backend/data.php",{"type": 6});

$.post("./backend/data.php",{"type": 1},function(res) {
	data = res;
	$('#median').bind('change', function(){useMedian ^= 1;display(curD)});
	sortProblems(0);
});

function rating2Str(rating,cnt) {
	var res = '<td style="';
	if(rating >= 2400) {
		res += 'color: red;';
	}else if(rating >= 2100) {
		res += 'color: rgb(255,140,0);';
	}else if(rating >= 1900) {
		res += 'color: rgb(170,0,170);';
	}else if(rating >= 1600) {
		res += 'color: blue;';
	}else if(rating >= 1400) {
		res += 'color: rgb(3,168,158);';
	}else if(rating >= 1200) {
		res += 'color: green;';
	}else if(rating >= 1000) {
		res += 'color: rgb(136,204,34);';
	}else{
		res += 'color: gray;';
	}
	res += 'font-weight: 500;" data-toggle="popover" data-content="Number of votes: ' + cnt + '" data-original-title="" title="">' + rating2Circle(rating) + (rating == null ? 'N/A' : Math.round(rating)) + '</td>';
	return res;
}

function rating2Circle(rating) {
	var percentage = 0;
	var col = "";
	if(rating >= 2400) {
		col += 'red';
		percentage = (rating - 2400) / 9;
	}else if(rating >= 2100) {
		col += 'rgb(255,140,0)';
		percentage = (rating - 2100) / 3;
	}else if(rating >= 1900) {
		col += 'rgb(170,0,170)';
		percentage = (rating - 1900) / 2;
	}else if(rating >= 1600) {
		col += 'blue';
		percentage = (rating - 1600) / 3;
	}else if(rating >= 1400) {
		col += 'rgb(3,168,158)';
		percentage = (rating - 1400) / 2;
	}else if(rating >= 1200) {
		col += 'green';
		percentage = (rating - 1200) / 2;
	}else if(rating >= 1000) {
		col += 'rgb(136,204,34)';
		percentage = (rating - 1000) / 2;
	}else{
		col += 'gray';
		percentage = (rating) / 10;
	}
	percentage = Math.round(10 * percentage) / 10;
	var res = '<span class="difficulty-circle" style="border-color: ' + col + '; background: linear-gradient(to top, ' + col + ' 0%, ' + col + ' ' + percentage + '%, rgba(0, 0, 0, 0) ' + percentage + '%, rgba(0, 0, 0, 0) 100%);"></span>';
	return res;
}

function quality2Str(quality,cnt) {
	var showQuality = Math.round(quality * 10) / 10;
	var res = '<td style="';
	if(quality == null) {
		res += 'color: gray;';
		showQuality = "N/A";
	}else{
		quality = Math.round(quality);
		if(quality == 1) {
			res += 'color: gray;';
		}else if(quality == 2) {
			res += 'color: rgb(144, 238, 144);';
		}else if(quality == 3) {
			res += 'color: rgb(80, 200, 120);';
		}else if(quality == 4) {
			res += 'color: rgb(34, 139, 34);';
		}else{
			res += 'color: rgb(0, 128, 0);';
		}
	}
	res += 'font-weight: 500;" data-toggle="popover" data-content="Number of votes: ' + cnt + '" data-original-title="" title="">' + showQuality + '</td>';
	return res;
}

function display(type) {
	$("#b" + curD).removeClass("active");
	$("#b" + type).addClass("active");
	curD = type;
	var p = $(".problems");
	p.empty();
	for(var i = 0;i < data.length;++i) {
		var curEntry = data[i];
		if(curEntry["type"] != type) continue;
		p.append("<tr>");
		p.append('<th scope="row">' + curEntry["contest"] + '</th>');
		p.append('<td><a href="' + curEntry["url"] + '" target="_blank">' + curEntry["name"] + '</a></td>');
		if(useMedian) {
			p.append(rating2Str(curEntry["rating2"],curEntry["cnt1"]));
			p.append(quality2Str(curEntry["quality2"],curEntry["cnt2"]));
		}else{
			p.append(rating2Str(curEntry["rating"],curEntry["cnt1"]));
			p.append(quality2Str(curEntry["quality"],curEntry["cnt2"]));
		}
		p.append("</tr>");
	}
	activatePopOver();
}

function monthToValue(s) {
	if(s == "Dec") return 3;
	if(s == "Open") return 2;
	if(s == "Feb") return 1;
	return 0;
}

function sortProblemsCmp(a,b) {
	if(a["type"] != b["type"]) return a["type"] - b["type"];
	if(sortMode == 0) {
		var aa = a["contest"].split(" ");
		var ab = b["contest"].split(" ");
		if(aa[0] != ab[0]) return ab[0] - aa[0];
		if(monthToValue(aa[1]) != monthToValue(ab[1])) return monthToValue(ab[1]) - monthToValue(aa[1]);
		return a["id"] - b["id"];
	}else if(sortMode == 1) {
		var ra = a["rating"] == null ? 0 : (useMedian ? a["rating2"] : a["rating"]);
		var rb = b["rating"] == null ? 0 : (useMedian ? b["rating2"] : b["rating"]);
		return rb - ra;
	}else{
		var ra = a["quality"] == null ? 0 : (useMedian ? a["quality2"] : a["quality"]);
		var rb = b["quality"] == null ? 0 : (useMedian ? b["quality2"] : b["quality"]);
		return rb - ra;
	}
}

function sortProblems(type) {
	sortMode = type;
	data.sort(sortProblemsCmp);
	display(curD);
}

function activatePopOver() {
	$(function () {$('[data-toggle="popover"]').popover({"trigger":"hover","placement":"auto"})})
}