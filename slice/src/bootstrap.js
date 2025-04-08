import * as css from "./css/styles.css"
import "./theme_scripts.js"

import '@fortawesome/fontawesome-free/js/fontawesome'
import '@fortawesome/fontawesome-free/js/solid'
import '@fortawesome/fontawesome-free/js/regular'
import '@fortawesome/fontawesome-free/js/brands'

import App from "./App.js";

import People from "./models/People.js";

// map pathname to models
const pathname2model = { '/people': People};

// build our application and get the model based on the url
const app = new App('app', pathname2model[window.location.pathname]);