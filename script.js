// Users
const UserForm = function(form,values = {},modal = null){

    // Initialize Values
    var Values = {
        name: null,
        title: null,
        role: null,
        email: null,
        address: null,
        city: null,
        country: null,
        state: null,
        zipcode: null,
        tollfree: null,
        phone: null,
        mobile: null,
        fax: null,
        website: null,
        tags: null,
        industries: null,
        locale: null,
    };

    // Set Values
    if(values){
        for(const [key, value] of Object.entries(values)){
            if(typeof Values[key] !== 'undefined'){
                switch(key){
                    case 'country':
                    case 'state':
                        Values[key] = value;
                        if(typeof value.code !== 'undefined') Values[key] = value.code;
                        break;
                    default:
                        Values[key] = value;
                        break;
                }
            }
        }
    }

    // name
    form.add(
        {
            name: 'name',
            label: builder.Locale.get('Name'),
            icon: 'hash',
            type: 'text',
            value: Values.name,
            class: {
                field: 'col-12',
                label: 'text-bg-primary',
            },
        }
    );

    // title
    form.add(
        {
            name: 'title',
            label: builder.Locale.get('Title'),
            icon: 'hash',
            type: 'text',
            value: Values.title,
            class: {
                field: 'col-6',
            },
        }
    );

    // role
    form.add(
        {
            name: 'role',
            label: builder.Locale.get('Role'),
            icon: 'hash',
            type: 'text',
            value: Values.role,
            class: {
                field: 'col-6',
            },
        }
    );

    // address
    form.add(
        {
            name: 'address',
            label: builder.Locale.get('Address'),
            icon: 'pin-map',
            type: 'text',
            value: Values.address,
            class: {
                field: 'col-7',
            },
        }
    );

    // city
    form.add(
        {
            name: 'city',
            label: builder.Locale.get('City'),
            icon: 'geo-alt',
            type: 'text',
            value: Values.city,
            class: {
                field: 'col-5',
            },
        }
    );

    // country
    form.add(
        {
            name: 'country',
            label: 'Country',
            icon: 'geo-alt',
            type: 'country',
            value: Values.country,
            modal: modal,
            class: {
                field: 'col',
            },
        }
    );

    // state
    form.add(
        {
            name: 'state',
            label: 'State',
            icon: 'geo-alt',
            type: 'state',
            value: Values.state,
            modal: modal,
            class: {
                field: 'col',
            },
        }
    );

    // zipcode
    form.add(
        {
            name: 'zipcode',
            label: builder.Locale.get('Zip Code'),
            icon: 'geo',
            type: 'zipcode',
            value: Values.zipcode,
            class: {
                field: 'col',
            },
        }
    );

    // email
    form.add(
        {
            name: 'email',
            label: builder.Locale.get('E-Mail'),
            icon: 'at',
            type: 'email',
            value: Values.email,
            class: {
                field: 'col-8',
                label: 'text-bg-primary',
            },
        }
    );

    // fax
    form.add(
        {
            name: 'fax',
            label: builder.Locale.get('Fax'),
            icon: 'telephone-outbound',
            type: 'phone',
            value: Values.fax,
            class: {
                field: 'col',
            },
        }
    );

    // phone
    form.add(
        {
            name: 'phone',
            label: builder.Locale.get('Phone'),
            icon: 'telephone',
            type: 'phone-extension',
            value: Values.phone,
            class: {
                field: 'col',
            },
        }
    );

    // mobile
    form.add(
        {
            name: 'mobile',
            label: builder.Locale.get('Mobile'),
            icon: 'telephone',
            type: 'phone-extension',
            value: Values.mobile,
            class: {
                field: 'col',
            },
        }
    );

    // tollfree
    form.add(
        {
            name: 'tollfree',
            label: builder.Locale.get('Toll Free'),
            icon: 'telephone-inbound',
            type: 'phone-international',
            value: Values.tollfree,
            class: {
                field: 'col',
            },
        }
    );

    // locale
    form.add(
        {
            name: 'locale',
            label: builder.Locale.get('Language'),
            icon: 'globe-americas',
            type: 'locale',
            value: Values.locale,
            modal: modal,
            class: {
                field: 'col-6',
                label: 'text-bg-primary',
            },
        }
    );

    // website
    form.add(
        {
            name: 'website',
            label: builder.Locale.get('Website'),
            icon: 'globe2',
            type: 'text',
            value: Values.website,
            class: {
                field: 'col-6',
            },
        }
    );

    // tags
    form.add(
        {
            name: 'tags',
            label: builder.Locale.get('Tags'),
            icon: 'tags',
            type: 'tags',
            value: Values.tags ?? [],
            modal: modal,
            class: {
                field: 'col-12',
            },
        }
    );

    // industries
    form.add(
        {
            name: 'industries',
            label: builder.Locale.get('Industries'),
            icon: 'building',
            type: 'industries',
            value: Values.industries ?? [],
            modal: modal,
            class: {
                field: 'col-12',
            },
        },
        function(input,form){
            input.removeClass('mb-3');
        },
    );
}
