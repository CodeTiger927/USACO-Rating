function writeRecord(name,value) {
    window.localStorage.setItem(name,value);
}

function readRecord(name) {
    return window.localStorage.getItem(name);
}