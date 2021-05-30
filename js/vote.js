var data = null;
var curD = 0;
var udata,urating,uquality;

function init() {
	copyOverCookies();
	
	var id = getUrlParameter("id");
	if(!id) {
		id = readRecord("uid");
	}
	if(!id) {
		alert("D: You need to use your invite link at least once first to activate this page.");
		window.location.replace("../");
	}else{
		writeRecord("uid",id,365);
		$.post("../backend/data.php",{"type": 2,"id": id},function(res) {
			if(res[0] == -1) {
				alert("Your invite link is invalid >.<");
				window.location.replace("../");
			}
			udata = res[0];
			urating = res[1];
			uquality = res[2];
			window.setTimeout(function(){displayVotes();addName();},300);
		});
	}

	$.post("../backend/data.php",{"type": 1},function(res) {
		data = res;
		sortProblems();
	});
}

$(init);

function displayVotes() {
	for(var i = 0;i < uquality.length;++i) {
		var q = $("#" + uquality[i].pid + "q");
		q.empty();
		if(uquality[i].val != -1) q.val(uquality[i].val);
	}
	for(var i = 0;i < urating.length;++i) {
		var r = $("#" + urating[i].pid + "r");
		r.empty();
		if(urating[i].val != -1) r.val(urating[i].val);
	}
}

function saveVotes() {
	var rating = [];
	var quality = [];
	for(var i = 1;i <= data.length;++i) {
		rating.push(-1);
		quality.push(-1);
	}
	for(var i = 1;i <= data.length;++i) {
		if(data[i - 1]["type"] != curD) {
			rating[data[i - 1]["id"] - 1] = -2;
			continue;
		}
		var v = $("#" + data[i - 1]["id"] + "r").val();
		if(v == "") {
			rating[data[i - 1]["id"] - 1] = -1;
		}else if($.isNumeric(v)){
			v = parseInt(v);
			if(v < 800 || v > 3500) {
				alert("Rating can only be between 800 to 3500!");
				return;
			}
			rating[data[i - 1]["id"] - 1] = v;
		}else{
			alert("Rating can only be integers >.< ");
			return;
		}
	}
	for(var i = 1;i <= data.length;++i) {
		if(data[i - 1]["type"] != curD) {
			quality[data[i - 1]["id"] - 1] = -2;
			continue;
		}
		var v = $("#" + data[i - 1]["id"] + "q").val();
		if(v == "") {
			quality[data[i - 1]["id"] - 1] = -1;
		}else if($.isNumeric(v)){
			v = parseInt(v);
			if(v < 1 || v > 5) {
				alert("Quality can only be between 1 to 5!");
				return;
			}
			quality[data[i - 1]["id"] - 1] = v;
		}else{
			alert("Quality can only be integers >.< ");
			return;
		}
	}
	var d = [rating,quality];
	$.post("../backend/data.php",{"type":4,"id":id,"votes":JSON.stringify(d)},function(res) {
		if(res["status"] == 1) {
			alert("Success!");
			location.reload();
		}else{
			console.log(res);
			// alert("Fail for some reason ;-;");
		}
	});
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
		p.append('<th scope="row" style="width: 21%">' + curEntry["contest"] + '</th>');
		p.append('<td style="width: 44%"><a href="' + curEntry["url"] + '" target="_blank">' + curEntry["name"] + '</a></td>');
		p.append('<td style="width: 17.5%"><input type="text" id="' + curEntry["id"] + 'r" class="vote-input" pattern="\\d+"></td>');
		p.append('<td style="width: 17.5%"><input type="text" id="' + curEntry["id"] + 'q" class="vote-input" pattern="\\d+"></td>');
		p.append("</tr>");
	}
	displayVotes();
}

function monthToValue(s) {
	if(s == "Dec") return 3;
	if(s == "Open") return 2;
	if(s == "Feb") return 1;
	return 0;
}

function sortProblemsCmp(a,b) {
	var aa = a["contest"].split(" ");
	var ab = b["contest"].split(" ");
	if(aa[0] != ab[0]) return ab[0] - aa[0];
	if(monthToValue(aa[1]) != monthToValue(ab[1])) return monthToValue(ab[1]) - monthToValue(aa[1]);
	return a["id"] - b["id"];
}

function addName() {
	$("#welcome").text("Welcome back " + udata["name"] + "!");
}

function sortProblems() {
	data.sort(sortProblemsCmp);
	display(curD);
}
