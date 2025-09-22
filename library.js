builder.add('layouts','user', class extends builder.ComponentClass {

    _init(){
        this._properties = {
            class: {
                component: null,
            },
            url: null,
            endpoint: null,
            table: 'users',
            id: null,
            disable: [],
            interval: 15000,
            autoStart: false,
            callback: {},
        };
        this._data = null;
        this._tabs = null;
        this._cards = {};
        this._widgets = {};
        this._interval = null;
    }

    _create(){

        // Set Self
        const self = this;

        // Create Component
        this._component = $(document.createElement('div')).attr({
            'id': 'user' + this._id,
            'class': 'user-layout',
        });
        this._component.id = this._component.attr('id');

        // Add Class
        if(this._properties.class.component){
            this._component.addClass(this._properties.class.component);
        }

        // Retrieve Records
        API.endpoint(self._properties.endpoint).execute(function(response){

            // Set Data
            self._data = response;
            self._properties.id = response.record.id;

            // Setup the layout
            self._component.row = $(document.createElement('div')).appendTo(self._component);
            self._component.details = $(document.createElement('div')).addClass('user-details').appendTo(self._component.row);
            self._component.col2 = $(document.createElement('div')).appendTo(self._component.row);

            // Create User Block
            self._component.details.avatar = $(document.createElement('div')).attr({
                'class': 'avatar cursor-pointer',
            }).appendTo(self._component.details).click(function(){
                self._builder.Widget('vcard',{mode:'upload',data: response.record.vcard.id});
            });
            self._component.details.avatar.img = $(document.createElement('img')).attr({
                'alt': 'Avatar',
                'src': '/avatar?username=' + response.record.username
            }).appendTo(self._component.details.avatar);
            // self._component.details.avatar = $(document.createElement('img')).attr({
            //     'class': 'avatar cursor-pointer',
            //     'alt': 'Avatar',
            //     'src': '/avatar?username=' + response.record.username
            // }).appendTo(self._component.details).click(function(){
            //     self._builder.Widget('vcard',{mode:'upload',data: response.record.vcard.id});
            // });
            self._component.details.meta = $(document.createElement('div')).addClass('meta').appendTo(self._component.details);
            self._component.details.meta.username = $(document.createElement('button')).attr({
                'class': 'username btn btn-link text-decoration-none',
                'type': 'button'
            }).text(response.record.vcard.name ?? response.record.username).appendTo(self._component.details.meta).click(function(){
                self._builder.Widget('vcard',{data: response.record.vcard.id});
            });
            self._component.details.meta.organization = $(document.createElement('button')).attr({
                'class': 'organization btn btn-link text-decoration-none',
                'type': 'button'
            }).text(response.record.organization.vcard.name).appendTo(self._component.details.meta).click(function(){
                self._builder.Widget('vcard',{data: response.record.organization.vcard.id});
            });
            self._component.details.meta.metadata = $(document.createElement('div')).addClass('metadata').appendTo(self._component.details.meta);
            const created = new Date(response.record.created ?? new Date().toISOString());
            self._component.details.meta.metadata.created = $(document.createElement('div')).attr({
                'class': 'metadata-item',
                'data-bs-toggle': 'tooltip',
                'data-bs-title': created.toLocaleString(),
            }).appendTo(self._component.details.meta.metadata);
            new bootstrap.Tooltip(self._component.details.meta.metadata.created);
            self._component.details.meta.metadata.created.label = $(document.createElement('span')).text(self._builder.Locale.get('Joined')).addClass('me-1').appendTo(self._component.details.meta.metadata.created);
            self._component.details.meta.metadata.created.icon = $(document.createElement('i')).addClass('bi bi-clock me-1').appendTo(self._component.details.meta.metadata.created);
            self._component.details.meta.metadata.created.timeago = $(document.createElement('time')).attr({
                'class': 'timeago',
                'datetime': response.record.created ?? new Date().toISOString(),
            }).appendTo(self._component.details.meta.metadata.created).timeago();
            const lastLogin = new Date(response.record.lastLogin ?? new Date().toISOString());
            self._component.details.meta.metadata.lastLogin = $(document.createElement('div')).attr({
                'class': 'metadata-item',
                'data-bs-toggle': 'tooltip',
                'data-bs-title': lastLogin.toLocaleString(),
            }).appendTo(self._component.details.meta.metadata);
            new bootstrap.Tooltip(self._component.details.meta.metadata.lastLogin);
            self._component.details.meta.metadata.lastLogin.label = $(document.createElement('span')).text(self._builder.Locale.get('Active')).addClass('me-1').appendTo(self._component.details.meta.metadata.lastLogin);
            self._component.details.meta.metadata.lastLogin.icon = $(document.createElement('i')).addClass('bi bi-clock me-1').appendTo(self._component.details.meta.metadata.lastLogin);
            self._component.details.meta.metadata.lastLogin.timeago = $(document.createElement('time')).attr({
                'class': 'timeago',
                'datetime': response.record.lastLogin ?? new Date().toISOString(),
            }).appendTo(self._component.details.meta.metadata.lastLogin).timeago();

            // Create a Tabs component
            self._builder.Component(
                "tabs",
                self._component.col2,
                {
                    class: {
                        navbar: 'nav-pills',
                    },
                },
                function(tabs,card){

                    // Set tabs
                    self.tabs(tabs);

                    // Styling
                    card._component.addClass('user-content');
                    card._component.body.removeClass('card-body');

                    // Notes
                    if(self._data.extensions.includes('notes') && !self._properties.disable.includes('notes')){

                        // Add the tab
                        tabs.add(
                            'notes',
                            {
                                icon: "stickies",
                                label: self._builder.Locale.get("Notes"),
                            },
                            function(tab,nav){
                                self._cards.notes = tab;
                                self._widgets.notes = self._builder.Widget('notes',tab,{data: self._data.dependencies.notes ?? {},targetTable: self._properties.table,targetId: self._properties.id})
                            },
                        );
                    }

                    // Contacts
                    if(self._data.extensions.includes('contacts') && !self._properties.disable.includes('contacts')){

                        // Add the Contacts tab
                        tabs.add(
                            'contacts',
                            {
                                icon: "person-vcard",
                                label: self._builder.Locale.get("Contacts"),
                            },
                            function(tab,nav){
                                self._cards.contacts = tab;
                                self._widgets.contacts = self._builder.Widget("contacts",tab,{data: self._data.dependencies.contacts ?? {},targetTable: self._properties.table,targetId: self._properties.id, default: self._data.record.vcard});
                            },
                        );
                    }

                    // Files
                    if(self._data.extensions.includes('files') && !self._properties.disable.includes('files')){

                        // Add the Files tab
                        tabs.add(
                            'files',
                            {
                                icon: "file-earmark",
                                label: self._builder.Locale.get("Files"),
                            },
                            function(tab,nav){
                                self._cards.files = tab;
                                self._widgets.files = self._builder.Widget("files",tab,{data: self._data.dependencies.files ?? {},targetTable: self._properties.table,targetId: self._properties.id,isPublic: 1});
                            },
                        );
                    }

                    // Documents
                    if(self._data.extensions.includes('documents') && !self._properties.disable.includes('documents')){

                        // Add the Documents tab
                        tabs.add(
                            'documents',
                            {
                                icon: "file-earmark-richtext",
                                label: self._builder.Locale.get("Documents"),
                            },
                            function(tab,nav){
                                self._cards.documents = tab;
                                self._widgets.documents = self._builder.Widget("documents",tab,{data: self._data.dependencies.documents ?? {},locale: self._data.record.vcard.locale,targetTable: self._properties.table,targetId: self._properties.id,docvals:{
                                    name: self._data.record.vcard.name,
                                    dba: self._data.record.vcard.dba,
                                    locale: self._data.record.vcard.locale,
                                    title: self._data.record.vcard.title,
                                    role: Array.isArray(self._data.record.vcard.role) ?self._data.record.vcard.role.join(', ') : self._data.record.vcard.role,
                                    address: self._data.record.vcard.address,
                                    city: self._data.record.vcard.city,
                                    state: self._data.record.vcard.state.name,
                                    zipcode: self._data.record.vcard.zipcode,
                                    country: self._data.record.vcard.country.name,
                                    phone: self._data.record.vcard.phone,
                                    mobile: self._data.record.vcard.mobile,
                                    tollfree: self._data.record.vcard.tollfree,
                                    fax: self._data.record.vcard.fax,
                                    email: self._data.record.vcard.email,
                                    website: self._data.record.vcard.website,
                                    tags: Array.isArray(self._data.record.vcard.tags) ?self._data.record.vcard.tags.join(', ') : self._data.record.vcard.tags,
                                    industries: Array.isArray(self._data.record.vcard.industries) ?self._data.record.vcard.industries.join(', ') : self._data.record.vcard.industries,
                                    businessNumber: self._data.record.vcard.businessNumber,
                                    taxExtension: self._data.record.vcard.taxExtension,
                                    importerExtension: self._data.record.vcard.importerExtension,
                                    avatar: '/avatar?id=' + self._data.record.vcard.id,
                                }});
                            },
                        );
                    }

                    // Event
                    if(self._data.extensions.includes('event') && !self._properties.disable.includes('event')){

                        // Add the Event tab
                        tabs.add(
                            'event',
                            {
                                icon: "activity",
                                label: self._builder.Locale.get("Activity"),
                            },
                            function(tab,nav){
                                self._cards.event = tab;
                                self._widgets.event = self._builder.Widget("events",tab,{data: self._data.dependencies.event ?? {},targetTable: self._properties.table,targetId: self._properties.id});
                            },
                        );
                    }
                },
            );
        });
    }

    tabs(tabs = null){
        if(tabs !== null){
            this._tabs = tabs;
        }
        return this._tabs;
    }
});

builder.add('widgets','users', class extends builder.ComponentClass {

    _init(){
        this._properties = {
            class: {
                component: null,
            },
            default: {},
            table: 'users',
            callback: {},
        };
    }

    _create(){

        // Set Self
        const self = this;

        // Create Component
        this._component = $(document.createElement('div')).attr({
            'id': 'users' + this._id,
            'class': 'users-widget',
        });
        this._component.id = this._component.attr('id');

        // Check if a component class is set
        if(this._properties.class.component){
            this._component.addClass(this._properties.class.component);
        }
    }

    create(callback = null){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                onEnter: false,
                icon: "plus-lg",
                title: this._builder.Locale.get("New User"),
                color: 'success',
                size: "xl",
                callback: {
                    load: function(component, modal){
                        return new Promise((resolve, reject) => {
                            try {
                                // Set the parent
                                const parent = component.dialog;

                                // Retrieve the libraries
                                API.endpoint('/library/fetch').execute(function(library){

                                    // Retrieve the vCard's roles
                                    API.endpoint('/categories/fetchAll').data({
                                        conditions: [
                                            {key: 'targetTable', operator: '=', value: 'vcards.role'},
                                        ]
                                    }).execute(function(response){

                                        // Create Role Options
                                        const roles = [];
                                        for(const [key, record] of Object.entries(response.records)){
                                            roles.push({id: record.name, text: builder.Locale.get(record.name)});
                                        }

                                        // Create the Form
                                        self._builder.Utility(
                                            'form',
                                            component.body,
                                            {
                                                class:{
                                                    component: 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3',
                                                },
                                                callback: {
                                                    val: function(values){
                                                        values.username = values.email;
                                                        values.isVerified = 1;
                                                        return values;
                                                    },
                                                    submit: function(form){

                                                        // Show the modal spinner
                                                        modal.spinner(true);

                                                        // AJAX request to create the record
                                                        API.endpoint('/users/create').data(form.val()).execute(function(response){

                                                            // Check if the callback is defined and execute it
                                                            if(typeof callback === 'function'){
                                                                callback(response);
                                                            }

                                                            // Close the modal
                                                            modal.hide();
                                                        },function(xhr, status, error){
                                                            modal.hide();
                                                        });
                                                    },
                                                }
                                            },
                                            function(form,component){

                                                // Add event listener on the modal submit button
                                                parent.content.footer.submit.click(function(e){
                                                    e.preventDefault();
                                                    e.stopPropagation();
                                                    form.submit();
                                                });

                                                // name
                                                form.add(
                                                    'text',
                                                    {
                                                        name: 'name',
                                                        label: self._builder.Locale.get('Name'),
                                                        placeholder: self._builder.Locale.get('Enter name'),
                                                        required: true,
                                                        class: {
                                                            component: 'col-12',
                                                            label: 'text-bg-primary',
                                                        },
                                                    }
                                                );
                                                // title
                                                form.add(
                                                    'text',
                                                    {
                                                        name: 'title',
                                                        label: self._builder.Locale.get('Title'),
                                                        placeholder: self._builder.Locale.get('Enter title'),
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // role
                                                form.add(
                                                    'select2',
                                                    {
                                                        name: 'role',
                                                        label: self._builder.Locale.get('Role'),
                                                        placeholder: self._builder.Locale.get('Select role(s)'),
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-8',
                                                        },
                                                        multiple: true,
                                                        options: roles,
                                                        allowClear: true,
                                                    }
                                                );
                                                // address
                                                form.add(
                                                    'text',
                                                    {
                                                        name: 'address',
                                                        label: self._builder.Locale.get('Address'),
                                                        placeholder: self._builder.Locale.get('Enter address'),
                                                        value: self._properties.default.address,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-7',
                                                        },
                                                    }
                                                );
                                                // city
                                                form.add(
                                                    'text',
                                                    {
                                                        name: 'city',
                                                        label: self._builder.Locale.get('City'),
                                                        placeholder: self._builder.Locale.get('Enter city'),
                                                        value: self._properties.default.city,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-5',
                                                        },
                                                    }
                                                );
                                                // country
                                                form.add(
                                                    'select2',
                                                    {
                                                        name: 'country',
                                                        label: self._builder.Locale.get('Country'),
                                                        placeholder: self._builder.Locale.get('Select country'),
                                                        value: self._properties.default.country,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                        options: library.options.countries,
                                                        callback: {
                                                            onChange: function(input, component){

                                                                // Check if the state input exists
                                                                if(!form._inputs.state){
                                                                    return;
                                                                }

                                                                // Clear the state select2 options
                                                                form._inputs.state.delete();

                                                                // Add the new options based on the selected country
                                                                for(const [key, option] of Object.entries(library.options.states[input.val()] || [])){
                                                                    form._inputs.state.add(option.id, option.text);
                                                                }

                                                                // Reset the state value
                                                                form._inputs.state.reset();
                                                            }
                                                        },
                                                    }
                                                );
                                                // state
                                                form.add(
                                                    'select2',
                                                    {
                                                        name: 'state',
                                                        label: self._builder.Locale.get('State'),
                                                        placeholder: self._builder.Locale.get('Select state'),
                                                        value: self._properties.default.state,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                        options: library.options.states[self._properties.default.country.code] || [],
                                                    }
                                                );
                                                // zipcode
                                                form.add(
                                                    'zipcode',
                                                    {
                                                        name: 'zipcode',
                                                        label: self._builder.Locale.get('Zipcode'),
                                                        placeholder: self._builder.Locale.get('Enter zipcode'),
                                                        value: self._properties.default.zipcode,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // email
                                                form.add(
                                                    'email',
                                                    {
                                                        name: 'email',
                                                        label: self._builder.Locale.get('Email'),
                                                        placeholder: self._builder.Locale.get('Enter email'),
                                                        required: true,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-8',
                                                            label: 'text-bg-primary',
                                                        },
                                                    }
                                                );
                                                // fax
                                                form.add(
                                                    'phone',
                                                    {
                                                        name: 'fax',
                                                        label: self._builder.Locale.get('Fax'),
                                                        placeholder: self._builder.Locale.get('Enter fax'),
                                                        value: self._properties.default.fax,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // phone
                                                form.add(
                                                    'phoneExt',
                                                    {
                                                        name: 'phone',
                                                        label: self._builder.Locale.get('Phone'),
                                                        placeholder: self._builder.Locale.get('Enter phone'),
                                                        value: self._properties.default.phone,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // mobile
                                                form.add(
                                                    'phone',
                                                    {
                                                        name: 'mobile',
                                                        label: self._builder.Locale.get('Mobile'),
                                                        placeholder: self._builder.Locale.get('Enter mobile'),
                                                        value: self._properties.default.mobile,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // tollfree
                                                form.add(
                                                    'phoneInt',
                                                    {
                                                        name: 'tollfree',
                                                        label: self._builder.Locale.get('Tollfree'),
                                                        placeholder: self._builder.Locale.get('Enter tollfree'),
                                                        value: self._properties.default.tollfree,
                                                        class: {
                                                            component: 'col-12 col-md-6 col-lg-4',
                                                        },
                                                    }
                                                );
                                                // locale
                                                form.add(
                                                    'select2',
                                                    {
                                                        name: 'locale',
                                                        label: self._builder.Locale.get('Locale'),
                                                        placeholder: self._builder.Locale.get('Select locale'),
                                                        value: self._properties.default.locale,
                                                        class: {
                                                            component: 'col-12',
                                                        },
                                                        options: library.options.locales,
                                                    }
                                                );

                                                // Resolve the promise
                                                resolve();
                                            },
                                        );
                                    },function(xhr, status, error){
                                        modal.hide();
                                        reject(error);
                                    });
                                },function(xhr, status, error){
                                    modal.hide();
                                    reject(error);
                                });
                            } catch(e) { reject(e); }
                        });
                    },
                },
            },
            function(modal,component){

                // Show the modal
                modal.show();
            },
        );
    }

    import(callback = null){

        // Set Self
        const self = this;

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "database-up",
                title: this._builder.Locale.get("Import Wizard"),
                color: 'teal',
                size: "xl",
                callback: {
                    submit: function(element,modal){

                        // Set Constants
                        const stepper = element.stepper;
                        const options = element.options;
                        const step = element.steps[element.current];
                        const form = step.form;

                        // Check the current step
                        switch(element.current){
                            case 'upload':
                                form.val().file.then(data => {
                                    // Check if a file was selected
                                    if(data.length > 0){

                                        // Select the file
                                        const file = data[Object.keys(data)[0]];

                                        // Generate a md5 checksum
                                        self._builder.Helper.md5(file.content.split(',')[1],function(checksum){

                                            // Save the checksum
                                            file.checksum = checksum;

                                            // Set additional properties
                                            file.targetTable = "users";
                                            file.isPublic = 1;

                                            // Check if the file is empty or if the file type is not supported
                                            if(file.json.length === 0){
                                                form.input('file').invalid(self._builder.Locale.get('The selected file is empty. Please select a valid file to proceed.'));
                                            } else {

                                                // Loop through the columns
                                                for(const [key, value] of Object.entries(file.json[Object.keys(file.json)[0]])){
                                                    options.push({id:key,text:key + ' - ' + value});
                                                }

                                                // Save the file in the step
                                                step.file = file;

                                                // Navigate to the next step
                                                stepper.next();
                                            }
                                        });
                                    } else {
                                        form.input('file').invalid(self._builder.Locale.get('Please select a file to proceed.'));
                                    }
                                }).catch(error => {
                                    console.error('Error reading files:', error);
                                    form.input('file').invalid(error)
                                });
                                return;
                            case 'users':
                                if(form.val().name && form.val().name !== 'none'){
                                    if(form.val().email && form.val().email !== 'none'){
                                        const promises = [];
                                        const users = [];
                                        for(const [key, record] of Object.entries(element.steps.upload.file.json)){
                                            const user = {};
                                            for(const [column, map] of Object.entries(element.steps.users.form.val())){
                                                user[column] = record[map] || null;
                                                if(user[column] === null || user[column] === ''){
                                                    delete user[column];
                                                }
                                            }
                                            if(typeof user.role !== 'undefined' && user.role){
                                                user.role = user.role.split(',').map(value => value.trim());
                                            }
                                            if(typeof user.name !== 'undefined' && user.name && typeof user.email !== 'undefined' && user.email){
                                                user.username = user.email;
                                                users.push(user);
                                            }
                                        }
                                        for(const [key, user] of Object.entries(users)){
                                            promises.push(function(bar){
                                                return new Promise((res, rej) => {
                                                    API.endpoint('/users/create').data(user).execute(function(response){
                                                        bar.removeClass('text-bg-success text-bg-danger').addClass('text-bg-primary');
                                                        if(typeof callback === 'function'){
                                                            callback(response);
                                                        }
                                                        res();
                                                    },function(xhr, status, error){
                                                        bar.removeClass('text-bg-primary text-bg-success').addClass('text-bg-danger');
                                                        rej(error);
                                                    });
                                                });
                                            });
                                        }
                                        modal.spinner(true);
                                        self._loader('teal', promises, function(){

                                            // Close the modal
                                            modal.hide();
                                        });
                                    } else {
                                        form.input('email').invalid(self._builder.Locale.get('Please select a field for Email to proceed.'));
                                    }
                                } else {
                                    form.input('name').invalid(self._builder.Locale.get('Please select a field for Name to proceed.'));
                                }
                                return;
                        }
                    },
                },
            },
            function(modal,component){

                // Set the parent
                const parent = component;

                // Styling
                parent.body.addClass('p-0');
                parent.body.controls = $(document.createElement('div')).addClass('p-3 py-2').appendTo(parent.body);
                parent.current = 'upload';
                parent.options = [];

                // Create the Stepper
                self._builder.Component(
                    'stepper',
                    component.body,
                    {
                        class: {
                            control: 'rounded-pill',
                            content: 'p-3 py-2',
                        },
                    },
                    function(stepper, component){

                        // Set the stepper in the element for later use
                        parent.stepper = stepper;
                        parent.steps = {};

                        // Styling
                        component.controls.appendTo(parent.body.controls);
                        component.pagination.addClass('d-none');

                        stepper.add(
                            {
                                label: self._builder.Locale.get('Upload'),
                                icon: 'upload',
                                class: {
                                    content: 'bg-gray-200 border-top',
                                },
                            },
                            function(step){
                                step.control.attr('data-bs-toggle', null);
                                step.content.on('show.bs.collapse', function () {
                                    parent.current = 'upload';
                                });
                                step.form = self._builder.Utility(
                                    'form',
                                    step.content,
                                    {},
                                    function(form,component){

                                        // Upload
                                        form.add(
                                            'excel',
                                            {
                                                name: 'file',
                                                placeholder: self._builder.Locale.get('Select file'),
                                            }
                                        );
                                    },
                                );
                                parent.steps.upload = step;
                            }
                        );
                        stepper.add(
                            {
                                label: self._builder.Locale.get('Users'),
                                icon: 'people',
                                class: {
                                    content: 'bg-gray-200 border-top',
                                },
                            },
                            function(step){
                                step.control.attr('data-bs-toggle', null);
                                step.content.on('show.bs.collapse', function () {
                                    parent.current = 'users';
                                    for(const [name, input] of Object.entries(step.form._inputs)){
                                        input.delete();
                                        for(const [key, option] of Object.entries(parent.options)){
                                            input.add(option.id, option.text);
                                        }
                                        input.val(name);
                                    }
                                });
                                step.form = self._builder.Utility(
                                    'form',
                                    step.content,
                                    {
                                        class: {
                                            component: 'row g-3',
                                        },
                                    },
                                    function(form,component){

                                        // Create a form to map the columns
                                        for(const [key, column] of Object.entries(['name', 'title', 'role', 'address', 'city', 'country', 'state', 'zipcode', 'email', 'fax', 'phone', 'mobile', 'tollfree', 'locale'])){

                                            // Create the select2
                                            form.add(
                                                'select2',
                                                {
                                                    name: column,
                                                    label: self._builder.Locale.get(self._builder.Helper.ucwords(column)),
                                                    placeholder: self._builder.Locale.get('Select a field'),
                                                    options: parent.options,
                                                    value: column,
                                                    required: (column === 'name' || column === 'email'),
                                                    class: {
                                                        component: 'col-12 col-md-6',
                                                        label: (column === 'name' || column === 'email') ? 'text-bg-primary' : '',
                                                    },
                                                }
                                            );
                                        }
                                    },
                                );
                                parent.steps.users = step;
                            }
                        );
                    }
                );

                // Show the modal
                modal.show();
            },
        );
    }

    _loader(color, promises, callback = null){

        // Set Self
        const self = this;

        // Check if promises contains records
        if(!promises || (Array.isArray(promises) && promises.length === 0) || !Array.isArray(promises) ){
            console.error('No records available to process.');
            return;
        }

        // Create the Modal
        this._builder.Component(
            "modal",
            {
                icon: "code-slash",
                title: this._builder.Locale.get("Processing..."),
                color: color,
                cancel: false,
                submit: false,
                static: true,
                size: "lg",
            },
            function(modal,component){

                // Set the parent
                const parent = component;

                // Styling
                component.body.addClass('bg-gray-200 p-3 py-2 rounded-bottom');

                // Create a progress bar
                component.progress = builder.Component(
                    'progress',
                    component.body,
                    {
                        size: '32px',
                        color: 'primary',
                        striped: true,
                        animated: true,
                        scale: promises.length,
                        label: "{percent} completed {progress} of {scale} records processed",
                    },
                    async function(progress,component){

                        // Set default value
                        progress.set(0);

                        // Show the modal
                        modal.show();

                        // Loop through the records
                        for(const [key, promise] of Object.entries(promises)){

                            // Execute the promises sequentially
                            await promise(component.bar);

                            // Set the value
                            progress.set((parseInt(key) + 1));
                        }

                        // Update the color of the progress bar
                        component.bar.removeClass('text-bg-primary text-bg-danger').addClass('text-bg-success');

                        // Check if a callback is provided
                        if (typeof callback === 'function') {
                            callback();
                        }

                        // Timeout to close the modal
                        setTimeout(function(){

                            // Close the modal
                            modal.hide();
                        }, 1000);
                    },
                );
            },
        );
    }
});
