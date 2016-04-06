function account(user_id)
{
	$.weeboxs.open(ROOT+'?c=User&a=account&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_ACCOUNT'],width:600,height:180});
}
function send_message(user_id){
    	$.weeboxs.open(ROOT+'?c=User&a=message&id='+user_id, {contentType:'ajax',showButton:false,title:'发送站内信',width:600,height:280});
}


function account_detail(user_id)
{
	location.href = ROOT + '?c=User&a=account_detail&id='+user_id;
}

function consignee(user_id)
{
	location.href = ROOT + '?c=User&a=consignee&id='+user_id;
}

function weibo(user_id)
{
	location.href = ROOT + '?c=User&a=weibo&id='+user_id;
}

function re_list(user_id)
{
	location.href = ROOT + '?c=User&a=re_list&id='+user_id;
}
