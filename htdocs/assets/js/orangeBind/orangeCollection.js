class orangeCollection {
    /* on construction */
    constructor(app, defaults)
    {
        /* don't forget to filter this out of collect() */
        this.app = app;

        if (defaults) {
            this.alter(defaults);
        }
    }

    /* add or change a collection property */
    alter(name, value)
    {
        if (typeof name === 'object') {
            for (let property in name) {
                this[property] = name[property];
            }
        } else {
            this[name] = value;
        }

        return this;
    }

    /* collect the objects property for export */
    collect()
    {
        let collection = {};

        for (let propertyName in this) {
            if (typeof this[propertyName] !== 'function' && propertyName !== 'app') {
                collection[propertyName] = this[propertyName];
            }
        }

        return collection;
    }

}