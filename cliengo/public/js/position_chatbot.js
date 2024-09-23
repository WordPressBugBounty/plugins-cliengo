// in case widget is v2, attempts will reach 0
var maxAttempts = 1000;
var interval = setInterval(function(){
  maxAttempts--;
	var converse_chat = document.getElementById("converse-chat");

	if (converse_chat) {
    converse_chat.style.right = "auto";
	}

	if (maxAttempts <= 0 || (converse_chat && converse_chat.style.right ===  "auto")) {
		clearInterval(interval);
	}

}, 50);



