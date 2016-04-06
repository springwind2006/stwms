function project_audit(id) {
    window.location = ROOT + '?c=Project&a=audit&type=1&id=' + id;
}

function project_unaudit(id) {
    window.location = ROOT + '?c=Project&a=audit&type=0&id=' + id;
}

function project_finance(id) {
    window.location = ROOT + '?c=Project&a=finance&id=' + id;
}

function project_team(id) {
    window.location = ROOT + '?c=Project&a=team&id=' + id;
}

function edit_investor(id) {
    window.location = ROOT + '?c=Project&a=investor&type=edit&id=' + id;
}
function del_investor(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=investor&type=del&id=' + id;
    }
}

function edit_invest_leader(id) {
    window.location = ROOT + '?c=Project&a=invest_leader&type=edit&id=' + id;
}
function del_invest_leader(id) {
    if (window.confirm("您确定要删除审核信息吗？删除后需要投资人重新申请！")) {
        window.location = ROOT + '?c=Project&a=invest_leader&type=del&id=' + id;
    }
}
function toogle_invest_leader_status(id, domobj) {  
    $.ajax({
        url: ROOT + "?" + VAR_MODULE + "=" + MODULE_NAME + "&" + VAR_ACTION + "=" + ACTION_NAME + "&type=is_leader&ajax=1&id=" + id,
        type: "GET",
        dataType: "json",
        success: function (obj) {
            if (obj.data == '1') {
                $(domobj).html(LANG['YES']);
            } else if (obj.data == '0') {
                $(domobj).html(LANG['NO']);
            } else if (obj.data == '-1') {
            }
            $("#info").html(obj.info);
        }
    });
}
function add_crowd_leader() {
    window.location = ROOT + '?c=Project&a=crowd_leader&type=add';
}
function edit_crowd_leader(id) {
    window.location = ROOT + '?c=Project&a=crowd_leader&type=edit&id=' + id;
}
function del_crowd_leader(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=crowd_leader&type=del&id=' + id;
    }
}
function add_sponsor() {
    window.location = ROOT + '?c=Project&a=sponsor&type=add';
}
function edit_sponsor(id) {
    window.location = ROOT + '?c=Project&a=sponsor&type=edit&id=' + id;
}
function del_sponsor(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=sponsor&type=del&id=' + id;
    }
}

function view_demo(id) {
    var url = ROOT + '?c=Project&a=investment&type=view_demo&id=' + id;
    $.weeboxs.open(url,
            {
                boxid: 'fanwe_success_box',
                contentType: 'ajax',
                showButton: true,
                showCancel: false,
                width: 400,
                height: 50,
                showOk: true,
                title: '查看投资感言'
            }
    );
}

function del_investment(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=investment&type=del&id=' + id;
    }
}

//经纪人
function add_broker() {
    window.location = ROOT + '?c=Project&a=broker&type=add';
}
function edit_broker(id) {
    window.location = ROOT + '?c=Project&a=broker&type=edit&id=' + id;
}
function del_broker(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=broker&type=del&id=' + id;
    }
}

//话题
function del_reply(id) {
    if (window.confirm("您确定要删除吗？删除后不可以恢复！")) {
        window.location = ROOT + '?c=Project&a=del_reply&id=' + id;
    }
}
function del_reply_list(e) {
    var a = $(e).parent().parent().remove();
}