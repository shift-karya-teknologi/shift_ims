const ClientState = Object.freeze({
  Offline     : 0,
  Ready       : 1,
  Used        : 2,
  Maintenance : 3
});

const UserGroup = Object.freeze({
  Unknown       : 0,
  Administrator : 1,
  Member        : 2,
  Guest         : 3
});

var ws;
var clients = new Map();

function is_connected() {
  return ws && ws.readyState === 1;
}

function reset_table_body() {
  document.querySelectorAll('.person').forEach(function(el) { el.innerHTML = ''; });
  document.querySelectorAll('.duration').forEach(function(el) { el.innerHTML = '--:--'; });
  document.querySelectorAll('.state').forEach(function(el) { el.innerHTML = ''; });
}

function update_table_row(id, client) {
  var stateElement = document.querySelector('#client-' + id + ' .state');
  var durationElement = document.querySelector('#client-' + id + ' .duration');
  var personElement = document.querySelector('#client-' + id + ' .person');
  
  if (client.state === ClientState.Offline) {
    stateElement.innerHTML = '<i class="material-icons left">close</i>';
    personElement.innerHTML = 'OFFLINE';
    durationElement.innerHTML = '--:--';
    return;
  }
  else if (client.state === ClientState.Ready) {
    stateElement.innerHTML = '<i class="material-icons left">check</i>';
    personElement.innerHTML = 'READY';
    durationElement.innerHTML = '--:--';
    return;
  }
  else if (client.state === ClientState.Maintenance) {
    stateElement.innerHTML = '<i class="material-icons left">build</i>';
    personElement.innerHTML = client.user.username;
    durationElement.innerHTML = '--:--';
    return;
  }

  if (client.user.group === UserGroup.Guest)
    stateElement.innerHTML = '<i class="material-icons left">accessibility</i>';
  else if (client.user.group === UserGroup.Member)
    stateElement.innerHTML = '<i class="material-icons left">perm_contact_calendar</i>';
  
  personElement.innerHTML = client.user.username;
  durationElement.innerHTML = duration_str(client.user.duration);
}

function set_ui_enabled(enabled){
  reset_table_body();
  document.querySelector('#server-indicator').innerHTML = enabled ? 'report_problem' : 'check_circle';
}

function uncheckAll() {
  document.querySelectorAll('.mdl-js-checkbox').forEach(function(el) {
    el.MaterialCheckbox.uncheck();
  });
}

function send(msgType, data) {
  if (!is_connected())
    return;
  
  var message = JSON.stringify(["client-monitor", msgType, data === undefined ? null : data]);
  console.log('sending message:', message);
  ws.send(message);
};

function str_pad_left(n, width, z) {
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function duration_str(duration) {
  var h = Math.floor(duration / 60);
  var m = duration - (h * 60);
  return str_pad_left(h, 2, '0') + ':' + str_pad_left(m, 2, '0');
}

function connect() {
  ws = new WebSocket(window['SNBS_URL']);

  ws.onopen = function() {
    send("init");
  };

  ws.onmessage = function (e) {
    var msg = JSON.parse(e.data);
    var type = msg[0];
    var data = msg[1];

    console.log(msg);

    if (type === 'init') {
      set_ui_enabled(true);

      clients = new Map();

      for (var i = 0; i < data.clients.length; i++) {
        var client = data.clients[i];
        clients.set(client.id, client);
        update_table_row(client.id, client);
      }

    }
    else if (type === 'client-session-start' || type === 'client-session-stop' || type === 'client-session-sync' || type === 'client-session-timeout'
      || type === 'client-maintenance-started' || type === 'client-maintenance-finished'
      || type === 'client-connected' || type === 'client-disconnected') {
      update_table_row(data.id, data);
    }
  };

  ws.onclose = function() {
    ws = null;
    set_ui_enabled(false);
    setTimeout(connect, 5000);
  }
  
  
}

function do_action(action, callback) {
  if (!is_connected()) return;
  var ids = [];
  document.querySelectorAll('tr.is-selected').forEach(function(el){
    var id = parseInt(el.id.replace('client-', ''));
    var client = clients.get(id);
    if (callback(client.state))
      ids.push(id);
  });
  send(action, ids);
  uncheckAll();
}

(function () {
  if (!("WebSocket" in window)) {
    console.log("Unsupported browser!");
    return;
  }
  
  var stopActionElement = document.getElementById('stop-action');
  var shutDownActionElement = document.getElementById('shutdown-action');
  var restartActionElement = document.getElementById('restart-action');
  
  function stop_callback(state) { return state === ClientState.Used || state === ClientState.Maintenance; }
  function shutdown_callback(state) { return state === ClientState.Ready; }
  function restart_callback(state) { return state !== ClientState.Offline; }
  
  stopActionElement.addEventListener('click', function() { do_action("stop-sessions", stop_callback); });
  shutDownActionElement.addEventListener('click', function() { do_action("shutdown-clients", shutdown_callback); });
  restartActionElement.addEventListener('click', function() { do_action("restart-clients", restart_callback); });
  
  connect();
})();
