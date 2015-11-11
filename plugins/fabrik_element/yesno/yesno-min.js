/*! Fabrik */
var FbYesno=my.Class(FbRadio,{constructor:function(a,b){this.plugin="fabrikyesno",FbYesno.Super.call(this,a,b)},getChangeEvent:function(){return this.options.changeEvent}});