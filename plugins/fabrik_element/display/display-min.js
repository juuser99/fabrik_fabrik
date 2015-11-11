/*! Fabrik */
var FbDisplay=my.Class(FbElement,{constructor:function(a,b){FbDisplay.Super.call(this,a,b)},update:function(a){this.getElement()&&(this.element.innerHTML=a)}});