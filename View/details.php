<div class="col-12" id="layout"></div>
<script>
    (async function () {
        await builder.Storage._ensureReady?.();
        $(document).ready(function(){
            $.ajax({
                url: '/api/users/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>',
                type: 'GET',dataType: 'json',
                error: function(xhr, status, error) {
                    let color = 'info', icon = 'question-circle', title = builder.Locale.get(xhr.statusText), content = builder.Locale.get(xhr.responseText);
                    switch(xhr.status){
                        case 403: color = 'danger'; icon = 'person'; break;
                        case 404: color = 'warning'; icon = 'question-diamond'; break;
                        case 500: color = 'danger'; icon = 'bug'; break;
                    }
                    builder.Component("alert","#layout",{icon:icon,color:color,title:title},function(alert,component){component.content.html('<pre class="m-0 p-2">'+content+'</pre>');});
                },
                success: async function(response) {

                    // Configure Storage
                    builder.Storage.setKey(`user:${response.record.id}`);
                    await builder.Storage.set(response);
                    console.log(await builder.Storage.get());

                    // Set the color, icon and label
                    var color = ['secondary','primary','success','warning','danger'];
                    var icon = ['ban','eye','plus-lg','pencil','trash'];
                    var label = ['None','Read','Create','Update','Delete'];

                    // Set the element
                    var element = $('#layout');

                    // Setup the layout
                    element.row = $(document.createElement('div')).addClass('row').appendTo(element);
                    element.col1 = $(document.createElement('div')).addClass('col-12 col-md-6 col-lg-4').appendTo(element.row);
                    element.col2 = $(document.createElement('div')).addClass('col-12 col-md-6 col-lg-8').appendTo(element.row);

                    // Create the Details Card
                    const Details = builder.Component(
                        "card",
                        element.col1,
                        {
                            icon: "person",
                            title: builder.Locale.get('Details'),
                        },
                        async function(card,component){

                            // Retrieve the record
                            let record = await builder.Storage.get('record');

                            // Styling
                            component.body.addClass('d-flex flex-column justify-content-center align-items-center');

                            // Insert the user's profile picture
                            component.body.avatar = $(document.createElement('div')).addClass('rounded-circle border border-3 border-light d-flex justify-content-center align-items-center position-relative').css({"height": "256px", "width": "256px"}).appendTo(component.body);
                            component.body.avatar.img = $(document.createElement('img')).attr({
                                "class": "rounded-circle",
                                "src": '/avatar?username=' + record.username + '&size=256',
                                "data-type": "avatar",
                                "data-vcard": record.vcard.id,
                                "style": "max-height: 250px; max-width: 250px; height: 250px; width: 250px; object-fit: contain; object-position: center;",
                            }).appendTo(component.body.avatar);
                            component.body.avatar.btn = $(document.createElement('button')).attr({
                                "type": "button",
                                "class": "btn btn-sm btn-info fs-5 rounded-circle position-absolute",
                                "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; bottom: 8px; right: 8px;",
                            }).html('<i class="bi bi-upload"></i>').appendTo(component.body.avatar);
                            component.body.avatar.btn.click(function(){
                                vCardModalAvatar(record.vcard);
                            });

                            // Insert the user's name
                            component.body.name = $(document.createElement('div')).addClass('position-relative mt-2 text-center').appendTo(component.body);
                            component.body.name.string = $(document.createElement('h2')).addClass('fw-lighter m-0').text(record.vcard.name).appendTo(component.body.name);
                            component.body.name.btn = $(document.createElement('button')).attr({
                                "type": "button",
                                "class": "btn btn-sm btn-warning fs-5 rounded-circle position-absolute",
                                "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; top: calc(50% - 24px); right: -56px;",
                            }).html('<i class="bi bi-pencil"></i>').appendTo(component.body.name);
                            component.body.name.btn.click(function(){
                                vCardModalEdit(record.vcard);
                            });

                            // Insert the user's organization
                            component.body.organization = $(document.createElement('div')).addClass('position-relative mt-2 text-center').appendTo(component.body);
                            component.body.organization.string = $(document.createElement('h4')).addClass('fw-lighter m-0').text(record.organization.vcard.name).appendTo(component.body.organization);
                            component.body.organization.btn = $(document.createElement('button')).attr({
                                "type": "button",
                                "class": "btn btn-sm btn-primary fs-5 rounded-circle position-absolute",
                                "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; top: calc(50% - 24px); right: -56px;",
                            }).html('<i class="bi bi-person-vcard"></i>').appendTo(component.body.organization);
                            component.body.organization.btn.click(function(){
                                vCardModal(record.organization.vcard.id);
                            });

                            // Insert the user's last login
                            component.body.lastLogin = $(document.createElement('div')).addClass('position-relative mt-2 text-center').appendTo(component.body);
                            component.body.lastLogin.header = $(document.createElement('h4')).addClass('m-0').appendTo(component.body.lastLogin);
                            component.body.lastLogin.badge = $(document.createElement('span')).attr({
                                "class": "badge text-bg-secondary rounded-pill fw-light user-select-none",
                                'data-bs-toggle': 'tooltip',
                                'data-bs-placement': 'bottom',
                                'data-bs-title': record.lastLogin,
                                'title': record.lastLogin,

                            }).appendTo(component.body.lastLogin.header);
                            component.body.lastLogin.badge.tooltip = new bootstrap.Tooltip(component.body.lastLogin.badge[0]);
                            component.body.lastLogin.badge.icon = $(document.createElement('i')).addClass('bi bi-clock me-1').appendTo(component.body.lastLogin.badge);
                            component.body.lastLogin.badge.time = $(document.createElement('time')).attr({
                                "class": "timeago",
                                "datetime": record.lastLogin,
                            }).text(record.lastLogin).appendTo(component.body.lastLogin.badge);
                            component.body.lastLogin.badge.time.timeago();
                        },
                    );

                    // Create a Tabs component
                    const Tabs = builder.Component(
                        "tabs",
                        element.col2,
                        {
                            class: {
                                navbar: 'nav-pills',
                            },
                        },
                        async function(tabs,card){

                            // Retrieve the record
                            let record = await builder.Storage.get('record');

                            // Set the table
                            let table = 'users'

                            // Styling
                            card._component.body.removeClass('card-body');

                            // Notes
                            <?php if($this->Helper->Core->isInstalled('notes')): ?>

                                // Retrieve the notes
                                let notes = await builder.Storage.get('dependencies:notes');

                                // Add the Notes tab
                                tabs.add(
                                    'notes',
                                    {
                                        icon: "stickies",
                                        label: builder.Locale.get("Notes"),
                                    },
                                    function(tab,nav){
                                        card.notes = tab;
                                        NotesFeed(notes ?? [], tab, table, record.id);
                                    },
                                );
                            <?php endif; ?>

                            // Contacts
                            <?php if($this->Helper->Core->isInstalled('contacts')): ?>

                                // Retrieve the contacts
                                let contacts = await builder.Storage.get('dependencies:contacts');

                                // Add the Contacts tab
                                tabs.add(
                                    'contacts',
                                    {
                                        icon: "person-vcard",
                                        label: builder.Locale.get("Contacts"),
                                    },
                                    function(tab,nav){
                                        card.contacts = tab;
                                        ContactsFeed(contacts ?? [], tab, {
                                            "category": "Contact",
                                            "address": record.vcard.address,
                                            "city": record.vcard.city,
                                            "country": record.vcard.country.code,
                                            "state": record.vcard.state.code,
                                            "zipcode": record.vcard.zipcode,
                                            "locale": record.vcard.locale,
                                            "phone": record.vcard.phone,
                                            "targetTable": table,
                                            "targetId": record.id,
                                        });
                                    },
                                );
                            <?php endif; ?>

                            // Files
                            <?php if($this->Helper->Core->isInstalled('files')): ?>

                                // Retrieve the files
                                let files = await builder.Storage.get('dependencies:files');

                                // Add the Files tab
                                tabs.add(
                                    'files',
                                    {
                                        icon: "file-earmark",
                                        label: builder.Locale.get("Files"),
                                    },
                                    function(tab,nav){
                                        card.files = tab;
                                        FilesFeed(files ?? [], tab, {
                                            targetTable: table,
                                            targetId: record.id,
                                            isPublic: 1,
                                        });
                                    },
                                );
                            <?php endif; ?>

                            // Documents
                            <?php if($this->Helper->Core->isInstalled('documents')): ?>

                                // Retrieve the documents
                                let documents = await builder.Storage.get('dependencies:documents');

                                // Add the Documents tab
                                tabs.add(
                                    'documents',
                                    {
                                        icon: "file-earmark-richtext",
                                        label: builder.Locale.get("Documents"),
                                    },
                                    function(tab,nav){
                                        card.files = tab;
                                        docvals = {
                                            "locale": record.vcard.locale,
                                            "name": record.vcard.name,
                                            "title": record.vcard.title,
                                            "address": record.vcard.address,
                                            "city": record.vcard.city,
                                            "state": record.vcard.state.code,
                                            "zipcode": record.vcard.zipcode,
                                            "country": record.vcard.country.code,
                                            "phone": record.vcard.phone,
                                            "mobile": record.vcard.mobile,
                                            "tollfree": record.vcard.tollfree,
                                            "fax": record.vcard.fax,
                                            "website": record.vcard.website,
                                            "businessNumber": record.vcard.businessNumber,
                                            "taxExtension": record.vcard.taxExtension,
                                            "importerExtension": record.vcard.importerExtension,
                                        };
                                        builder.Helper.urlToBase64("/plugin/leads/logo?id="+builder.Storage.get('record:id')).then(dataURI => {
                                            docvals.avatar = dataURI;
                                            DocumentsFeed(documents ?? [], tab, {
                                                targetTable: "leads",
                                                targetId: record.id,
                                                isPublic: 1,
                                                locale: record.vcard.locale,
                                                docvals: docvals,
                                            }, record.vcard.locale);
                                        });
                                    },
                                );
                            <?php endif; ?>

                            // Event
                            <?php if($this->Helper->Core->isInstalled('event')): ?>

                                // Retrieve the event
                                let event = await builder.Storage.get('dependencies:event');

                                // Add the Event tab
                                tabs.add(
                                    'activities',
                                    {
                                        icon: "activity",
                                        label: builder.Locale.get("Activity"),
                                    },
                                    function(tab,nav){
                                        tab.addClass('px-4 py-3');
                                        card.activities = tab;
                                        EventFeed(event ?? [], tab);
                                    },
                                );
                            <?php endif; ?>

                            // Relationship
                            <?php if($this->Helper->Core->isInstalled('relationship')): ?>

                                // Retrieve the relationship
                                let relationship = await builder.Storage.get('dependencies:relationship');

                                // Add the Relationship tab
                                tabs.add(
                                    'related',
                                    {
                                        icon: "diagram-2",
                                        label: builder.Locale.get("Related"),
                                    },
                                    function(tab,nav){
                                        tab.addClass('px-4 py-3');
                                        card.related = tab;
                                        RelationshipFeed(relationship, tab, table, record.id, function(feed){
                                            card.related.feed = feed;
                                        });
                                    },
                                );
                            <?php endif; ?>
                        },
                    );
                },
            });
        });
    })();
</script>
