/*! Fabrik */
var FbDisplay=my.Class(FbElement,{constructor:function(a,b){this.parent(a,b)},update:function(a){this.getElement()&&(this.element.innerHTML=a)}});