"""Screenshots of the CourseCare block and overview page through the WebDriver of the demo Selenium container.

Usage: python3 shots003.py USER PASSWORD OUTDIR desktop|mobile kind:name=path [...]
kind: block (the block element), page (the whole page), pageopen (the whole page with the technical details open).
A local forwarder makes http://localhost:8051 (the wwwroot of the demo site) reach the webserver container.
"""
import base64, json, socket, socketserver, sys, threading, time, urllib.request

class Forward(socketserver.BaseRequestHandler):
    def handle(self):
        upstream = socket.create_connection(('webserver', 80))
        def pipe(a, b):
            try:
                while True:
                    data = a.recv(65536)
                    if not data:
                        break
                    b.sendall(data)
            except OSError:
                pass
            finally:
                for s in (a, b):
                    try:
                        s.shutdown(socket.SHUT_RDWR)
                    except OSError:
                        pass
        t = threading.Thread(target=pipe, args=(upstream, self.request), daemon=True)
        t.start()
        pipe(self.request, upstream)
        t.join()

class Server(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    daemon_threads = True

threading.Thread(target=Server(('127.0.0.1', 8051), Forward).serve_forever, daemon=True).start()

WD = 'http://localhost:4444'
def call(method, path, body=None):
    req = urllib.request.Request(WD + path, method=method, data=None if body is None else json.dumps(body).encode(),
                                 headers={'Content-Type': 'application/json'})
    with urllib.request.urlopen(req) as r:
        return json.loads(r.read())['value']

user, password, outdir, device, *items = sys.argv[1:]
options = {'args': ['--hide-scrollbars']}
if device == 'mobile':
    options['mobileEmulation'] = {'deviceMetrics': {'width': 375, 'height': 3000, 'pixelRatio': 2}}
sid = call('POST', '/session', {'capabilities': {'alwaysMatch': {'browserName': 'chrome',
    'goog:chromeOptions': options}}})['sessionId']
base = f'/session/{sid}'
def find(css):
    return list(call('POST', base + '/element', {'using': 'css selector', 'value': css}).values())[0]
def script(js):
    return call('POST', base + '/execute/sync', {'script': js, 'args': []})
def go(path):
    call('POST', base + '/url', {'url': 'http://localhost:8051' + path})
    time.sleep(1.5)
try:
    if device != 'mobile':
        call('POST', base + '/window/rect', {'width': 1280, 'height': 900})
    go('/login/index.php')
    call('POST', base + f'/element/{find("#username")}/value', {'text': user})
    call('POST', base + f'/element/{find("#password")}/value', {'text': password})
    script('document.querySelector("#login").submit();')
    time.sleep(3)
    print('logged in at', script('return location.href;'))
    for item in items:
        kind, rest = item.split(':', 1)
        name, path = rest.split('=', 1)
        if device != 'mobile':
            call('POST', base + '/window/rect', {'width': 1280, 'height': 900})
        go(path)
        # The demo site shares the developer config.php (performance footer); hide that footer only.
        script('document.querySelectorAll(".performanceinfo, #page-footer .debuggingmessage").forEach(e => e.remove());')
        if kind == 'block':
            png = call('GET', base + f'/element/{find("section.block_coursecare")}/screenshot')
        else:
            if kind == 'pageopen':
                script('document.querySelectorAll(".block_coursecare-overview details").forEach(d => d.open = true);')
            height = script('return document.documentElement.scrollHeight;')
            if device != 'mobile':
                call('POST', base + '/window/rect', {'width': 1280, 'height': min(int(height) + 150, 6000)})
                time.sleep(0.5)
                png = call('GET', base + '/screenshot')
            else:
                png = call('GET', base + f'/element/{find(".block_coursecare-overview")}/screenshot')
        with open(f'{outdir}/{name}.png', 'wb') as f:
            f.write(base64.b64decode(png))
        title = script('return document.title;')
        print(name, '|', title)
finally:
    call('DELETE', base)
