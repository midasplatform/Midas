var midas = midas || {};
midas.example = midas.example || {};



midas.example.sampleView = function (displayValue) {
  $('.viewWrapper').append(displayValue);
};



$(document).ready(function () {
    if(json.json_sample) {
        midas.example.sampleView(json.json_sample);
    }
});