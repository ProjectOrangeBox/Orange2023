import _ from 'lodash';
import tinybind from 'tinybind';
import formatters from './formatters.js';
import 


function component() {
    const element = document.createElement('div');
  
    // Lodash, now imported by this script
    element.innerHTML = _.join(['Hello', 'World'], ' ');

    let tb = tinybind(document.getElementById('app'), {auction: auction});
  
    return element;
  }
  
  document.body.appendChild(component());