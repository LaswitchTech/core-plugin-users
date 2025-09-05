<article id="layout"></article>
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('index',"#layout",{
                url: '/api/users/fetchAll',
                conditions: [
                    {key: 'isDeleted', operator: '<>', value: 1},
                    {key: 'isArchived', operator: '<>', value: 1},
                ],
                dblclick: function(event, table, dt, node, data){
                    window.location.href = "/plugin/users/details?id=" + data.id + "&name=" + data.username;
                },
                actions: {
                    details:{
                        label:'Details',
                        icon:'eye',
                        action:function(event, table, dt, node, row, data){
                            window.location.href = "/plugin/users/details?id=" + data.id + "&name=" + data.username;
                        }
                    },
                },
                buttons: [
                    {
                        className : 'btn-success',
                        init: function (dt, node){
                            $(node).removeClass('btn-secondary');
                        },
                        text: '<i class="bi bi-plus-lg"></i>',
                        action:function(e, dt, node, config){
                            builder.Widget('users', {
                                default: {
                                    address: "<?= $this->Auth->user()->organization()->address ?>",
                                    city: "<?= $this->Auth->user()->organization()->city ?>",
                                    country: "<?= $this->Auth->user()->organization()->country ?>",
                                    state: "<?= $this->Auth->user()->organization()->state ?>",
                                    zipcode: "<?= $this->Auth->user()->organization()->zipcode ?>",
                                    fax: "<?= $this->Auth->user()->organization()->fax ?>",
                                    phone: "<?= $this->Auth->user()->organization()->phone ?>",
                                    mobile: "<?= $this->Auth->user()->organization()->mobile ?>",
                                    tollfree: "<?= $this->Auth->user()->organization()->tollfree ?>",
                                    locale: "<?= $this->Auth->user()->organization()->locale ?>",
                                },
                            }).create(function(response){

                                // Add the record to the table
                                dt.row.add(response.record).draw();
                            });
                        },
                    },
                    {
                        className : 'btn-teal',
                        init: function (dt, node){
                            $(node).removeClass('btn-secondary');
                        },
                        text: '<i class="bi bi-database-up"></i>',
                        action:function(e, dt, node, config){
                            builder.Widget('users').import(function(response){

                                // Add the record to the table
                                dt.row.add(response.record).draw();
                            });
                        },
                    },
                ],
                columns: [
                    {
                        targets: 0,
                        visible: false,
                        title: builder.Locale.get('ID'),
                        name: 'id',
                        data: 'id',
                        defaultContent: '',
                    },
                    {
                        targets: 1,
                        visible: true,
                        className: 'all',
                        responsivePriority: 1,
                        title: builder.Locale.get('Username'),
                        name: 'username',
                        data: 'username',
                        defaultContent: '',
                    },
                    {
                        targets: 2,
                        visible: true,
                        className: 'min-md',
                        responsivePriority: 10,
                        title: builder.Locale.get('Name'),
                        name: 'name',
                        data: 'vcard.name',
                        defaultContent: '',
                    },
                    {
                        targets: 3,
                        visible: true,
                        className: 'min-md',
                        responsivePriority: 20,
                        title: builder.Locale.get('Last Login'),
                        name: 'lastLogin',
                        data: 'lastLogin',
                        defaultContent: '',
                    },
                ],
            });
        });
    })();
</script>
