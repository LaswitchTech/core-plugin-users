<div class="col-12" id="layout"></div>
<script>
    $(document).ready(function(){
        $.ajax({
            url: '/api/users/fetchAll',
            headers: {'X-CSRF-Authorization': CSRF_KEY},
            type: 'POST',dataType: 'json',
            data: {
                conditions: [
                    {key: 'isDeleted', operator: '<>', value: 1},
                    {key: 'isArchived', operator: '<>', value: 1},
                ]
            },
            error: function(xhr, status, error) {
                let color = 'info', icon = 'question-circle', title = builder.Locale.get(xhr.statusText), content = builder.Locale.get(xhr.responseText);
                switch(xhr.status){
                    case 403: color = 'danger'; icon = 'person'; break;
                    case 404: color = 'warning'; icon = 'question-diamond'; break;
                    case 500: color = 'danger'; icon = 'bug'; break;
                }
                builder.Component("alert","#layout",{icon:icon,color:color,title:title},function(alert,component){component.content.html('<pre class="m-0 p-2">'+content+'</pre>');});
            },
            success: function(response) {

                // Configure Storage
                builder.Storage.setKey('users');
                builder.Storage.set(response);
                console.log(builder.Storage.get())

                // Set Actions
                var actions = {
                    details:{
                        label:'Details',
                        icon:'eye',
                        action:function(event, table, dt, node, row, data){
                            window.location.href = "/plugin/users/details?id=" + data.id + "&name=" + data.username;
                        }
                    },
                };

                // Set Buttons
                var buttons = [
                    {
                        className : 'btn-success',
                        init: function (dt, node){
                            $(node).removeClass('btn-secondary');
                        },
                        text: '<i class="bi bi-plus-lg me-2"></i>'+builder.Locale.get('Add User'),
                        action:function(e, dt, node, config){
                            builder.Component(
                                "modal",
                                {
                                    callback: {
                                        submit: function(element,modal){
                                            element.form.submit();
                                        },
                                    },
                                    onEnter: true,
                                    destroy:true,
                                    icon: "plus-lg",
                                    title: builder.Locale.get("Add User"),
                                    cancel: true,
                                    submit: true,
                                    size: 'xl',
                                },
                                function(modal,component){

                                    // Save Modal Component for select2 fields
                                    const componentModal = component;

                                    // Set colors to the modal's header
                                    component.header.addClass('text-bg-success');

                                    // Change the label of the submit button
                                    component.footer.submit.text('Create').addClass('btn-success').removeClass('btn-link');

                                    // Add icon to the submit button
                                    component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-stars me-1').prependTo(component.footer.submit);

                                    // Create Form
                                    component.form = builder.Component(
                                        'form',
                                        component.body,
                                        {
                                            class:{
                                                form: 'row row-cols-3',
                                                field: 'mb-3 col',
                                            },
                                            callback:{
                                                val: function(values){
                                                    values.username = values.email;
                                                    values.isVerified = 1;
                                                    return values;
                                                },
                                                submit: function(form){
                                                    $.ajax({
                                                        url: '/api/users/create',
                                                        headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                        type: 'POST',dataType: 'json',
                                                        data: form.val(),
                                                        success: function(response) {

                                                            // Add the followup to the datatable
                                                            dt.row.add(response.record).draw();

                                                            // Close the modal
                                                            modal.hide();
                                                        }
                                                    });
                                                },
                                            },
                                        },
                                        function(form,component){

                                            // Generate Form
                                            UserForm(form,{
                                                "address": "<?= $this->Auth->user()->organization()->address ?>",
                                                "city": "<?= $this->Auth->user()->organization()->city ?>",
                                                "country": "<?= $this->Auth->user()->organization()->country ?>",
                                                "state": "<?= $this->Auth->user()->organization()->state ?>",
                                                "zipcode": "<?= $this->Auth->user()->organization()->zipcode ?>",
                                                "fax": "<?= $this->Auth->user()->organization()->fax ?>",
                                                "phone": "<?= $this->Auth->user()->organization()->phone ?>",
                                                "mobile": "<?= $this->Auth->user()->organization()->mobile ?>",
                                                "tollfree": "<?= $this->Auth->user()->organization()->tollfree ?>",
                                                "website": "<?= $this->Auth->user()->organization()->website ?>",
                                                "locale": "<?= $this->Auth->user()->organization()->locale ?>",
                                            },componentModal);

                                            //Show the modal
                                            modal.show();
                                        },
                                    );
                                },
                            );
                        },
                    }
                ];

                // Layout
                builder.Layout(
                    "list",
                    "#layout",
                    {
                        title: builder.Locale.get('Users'),
                        icon: 'person',
                        advancedSearch:true,
                        exportTools:true,
                        columnsVisibility:true,
                        selectTools:false,
                        showButtonsLabel: false,
                        dblclick:function(event, table, dt, node, data){
                            actions.details.action(event, table, dt, node, null, data);
                        },
                        actions:actions,
                        buttons:buttons,
                        columnDefs:[
                            { target: 0, visible: false, title: builder.Locale.get('ID'), name: 'id', data: 'id', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(data)
                                return object.prop('outerHTML');
                            }},
                            { target: 1, visible: true, title: builder.Locale.get('Username'), name: 'username', data: 'username', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(data)
                                return object.prop('outerHTML');
                            }},
                            { target: 2, visible: true, title: builder.Locale.get('Name'), name: 'name', data: 'name', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(row.vcard.name)
                                return object.prop('outerHTML');
                            }},
                            { target: 3, visible: true, title: builder.Locale.get('Last Login'), name: 'lastLogin', data: 'lastLogin', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(data)
                                return object.prop('outerHTML');
                            }},
                        ],
                    },
                    function(layout, component){

                        // Set container
                        var container = component.card._component.body;

                        // Lower the z-index of the table
                        component.table._component.table.addClass('z-2');

                        // Add Records to Layout
                        for(const [key, record] of Object.entries(builder.Storage.get('records'))){
                            layout.add(record);
                        }
                    },
                );
            },
        });
    });
</script>
