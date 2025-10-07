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
                    window.location.href = "/security/users/details?id=" + data.id + "&name=" + data.username;
                },
                actions: {
                    details:{
                        label:'Details',
                        icon:'eye',
                        action:function(event, table, dt, node, row, data){
                            window.location.href = "/security/users/details?id=" + data.id + "&name=" + data.username;
                        }
                    },
                    reset:{
                        label:'Reset Password',
                        icon:'person-lock',
                        action:function(event, table, dt, node, row, data){
                            builder.Widget('users', {id: data.username}).reset(function(response){

                                // Show a toast message
                                builder.Toast.add({
                                    color: 'success',
                                    icon: 'check-circle',
                                    title: builder.Locale.get('Success'),
                                    body: builder.Locale.get('The password has been reset and an email has been sent to the user.'),
                                });
                            });
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
                        render: function(value, data, type){

                            // Handle sorting
                            if (type === 'sort') {
                                return value ? Date.parse(value) : Number.MAX_SAFE_INTEGER;
                            }

                            // Check if the value is empty or null
                            if(value === null || value === ''){
                                return '';
                            }

                            // Setup tooltip and timeago
                            setInterval(function(){
                                $('[data-type="lastLogin"]:not(.rendered)').each(function(){
                                    const tooltip = new Date($(this).find('time').attr('datetime') ?? new Date().toISOString());
                                    $(this).attr({
                                        'data-bs-toggle': 'tooltip',
                                        'data-bs-title': tooltip.toLocaleString(),
                                    }).addClass('rendered');
                                    new bootstrap.Tooltip($(this));
                                    $(this).find('time').timeago();
                                });
                            },100);

                            // Return the formatted date
                            return '<div data-type="lastLogin"><i class="bi bi-clock me-1"></i><time datetime="'+value+'"></time></div>';
                        },
                    },
                ],
            });
        });
    })();
</script>
