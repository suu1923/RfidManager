define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rfid/revise/index' + location.search,
                    table: 'rfid_revise',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit: false,
                escape:false,
                columns: [
                    [
                        {field: 'rfid_id', title: __('R_id')},
                        {field: 'submission_id', title: __('Submission_id'),visible: (Config.oneself ? false : true)},
                        {field: 'auditor_id', title: __('Auditor_id'),visible:(Config.oneself ? false : true)},
                        {field: 'type', title: __('Type'),formatter:Table.api.formatter.flag,custom:{'1':'info'},searchList:{1:"属性"}},
                        {field: 'attr', title: __('Attr')},
                        {field: 'reason', title: __('Sub_reason')},
                        {field: 'success', title: __('Success'), searchList: {"0":__('Success 0'),"1":__('Success 1')},custom:{'0':'danger','1':'success'}, formatter: Table.api.formatter.normal},
                        // {field: 'notice_id', title: __('Notice_id')},
                        {field: 'reject_reason', title: __('Reject_reason')},
                        {field: 'option', title: __('Option'), searchList: {"0":__('Option 0'),"1":__('Option 1')}, formatter: Table.api.formatter.flag,custom:{'0':'danger','1':'success'}},
                        // {field: 'read', title: __('Read'), searchList: {"0":__('Read 0'),"1":__('Read 1')}, formatter: Table.api.formatter.normal},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'option_time', title: __('Option_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),visible:(Config.oneself ? true : false), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')},custom:{'0':'success','1':'danger'}, formatter: Table.api.formatter.status},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,

                            buttons:[
                                // 审核...协会及工作人员
                                {
                                    name:"option",//adopt
                                    title:__('Approval'),
                                    classname:  'btn btn-xs btn-info btn-info btn-dialog',
                                    text: __('Approval'),
                                    url:'rfid/revise/option',
                                    extend:'data-area=["400px","400px"]',
                                    // visible:("{:$oneself}" ? true : ""),
                                    hidden:function (row) {
                                        // 如果已操作，隐藏掉
                                        if (row.option == 1 || Config.oneself){
                                            return true;
                                        }
                                    },
                                    refresh:true, // 自动刷新
                                    callback: function (data) {
                                        console.log("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                },
                                // 撤回
                                {
                                    name:'withdraw',
                                    title: __('Withdraw'),
                                    classname:  'btn btn-xs btn-info btn-info btn-ajax',
                                    text: __('Withdraw'),
                                    url:"rfid/revise/withdraw",
                                    hidden:function (row) {
                                        // 如果已操作，隐藏掉
                                        // console.log(Config.oneself)
                                        if (row.option == 1 || row.status != 0 || Config.oneself!=true){
                                            return true;
                                        }
                                    },
                                    confirm:"确认撤回?",
                                    refresh:true, // 自动刷新
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        },
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        option: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});