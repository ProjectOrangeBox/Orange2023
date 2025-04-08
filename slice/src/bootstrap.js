import * as bootstrap from 'bootstrap'
import $ from 'jquery';
import tinybind from 'tinybind';
import App from "./App.js";

import "./cdn/fontawesome.js";
import "./cdn/sprintf.js";

import "./binders.js";
import "./formatters.js";
import "./theme_scripts.js"

import People from "./models/People.js";

import * as css from "./css/styles.css"

var app = new App('app', People);