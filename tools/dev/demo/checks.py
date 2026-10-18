"""Accessibility and responsiveness checks of the CourseCare block and overview page (task T048), through the
WebDriver of the demo Selenium container. Development tool only; it is not part of the plugin package.

Usage: python3 checks.py USER PASSWORD OUTDIR check:name=path [...]
check:
  axe     axe-core (the copy shipped with Moodle) on the whole page, WCAG 2.0/2.1/2.2 A and AA rules.
  zoom    1280 px window at 200% zoom (640 CSS px, device scale 2): horizontal overflow and a screenshot.
  narrow  320 CSS px (400% zoom of 1280 px): horizontal overflow and a screenshot.
  keys    Tab through the page: every focus stop inside the block or the page, whether a focus indicator is
          drawn, and Enter on each <summary> (open state before and after).
  tree    The accessibility tree that Chrome exposes for the block or the page (role, name, state). This is what
          assistive technologies receive; it is not a test with a screen reader.
Results are written to OUTDIR/NAME.json (and NAME.png for zoom and narrow).
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

user, password, outdir, *items = sys.argv[1:]
sid = call('POST', '/session', {'capabilities': {'alwaysMatch': {'browserName': 'chrome',
    'goog:chromeOptions': {'args': ['--hide-scrollbars']}}}})['sessionId']
base = f'/session/{sid}'
def find(css):
    return list(call('POST', base + '/element', {'using': 'css selector', 'value': css}).values())[0]
def script(js, args=None):
    return call('POST', base + '/execute/sync', {'script': js, 'args': args or []})
def ascript(js):
    return call('POST', base + '/execute/async', {'script': js, 'args': []})
def cdp(cmd, params=None):
    return call('POST', base + '/goog/cdp/execute', {'cmd': cmd, 'params': params or {}})
def go(path):
    call('POST', base + '/url', {'url': 'http://localhost:8051' + path})
    time.sleep(1.5)
    # Developer footer of the shared config.php and core user tours: not part of the plugin.
    script('document.querySelectorAll(".performanceinfo, #page-footer .debuggingmessage, [data-flexitour]")'
           '.forEach(e => e.remove());')
def press(key):
    call('POST', base + '/actions', {'actions': [{'type': 'key', 'id': 'kb', 'actions': [
        {'type': 'keyDown', 'value': key}, {'type': 'keyUp', 'value': key}]}]})
def metrics(width, scale):
    cdp('Emulation.setDeviceMetricsOverride', {'width': width, 'height': 900, 'deviceScaleFactor': scale,
                                               'mobile': False})
REGION = '.block_coursecare-overview, section.block_coursecare'

def overflow():
    return script('''
        const doc = document.documentElement;
        const regions = [...document.querySelectorAll(arguments[0])].map(e => ({
            region: e.className.split(' ')[0], scrollwidth: e.scrollWidth, clientwidth: e.clientWidth}));
        return {viewport: window.innerWidth, pagescroll: doc.scrollWidth > doc.clientWidth,
                pagescrollwidth: doc.scrollWidth, regions: regions};''', [REGION])

def axe():
    script('const s = document.createElement("script"); s.src = "/lib/behat/axe/axe.min.js"; '
           's.id = "cc-axe"; document.head.append(s);')
    time.sleep(1.5)
    return ascript('''
        const done = arguments[arguments.length - 1];
        axe.run(document, {runOnly: {type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']}})
            .then(r => done({passes: r.passes.length,
                incomplete: r.incomplete.map(v => ({id: v.id, targets: v.nodes.map(n => n.target.join(' ')),
                    reasons: v.nodes.map(n => n.any.map(a => a.message).join(' '))})),
                violations: r.violations.map(v => ({id: v.id, impact: v.impact,
                    targets: v.nodes.map(n => n.target.join(' ')),
                    inplugin: v.nodes.some(n => n.target.join(' ').includes('coursecare'))}))}))
            .catch(e => done({error: String(e)}));''')

def keys():
    """Tab until the focus comes back to a stop of the plugin already visited (at most 400 times); Enter on every
    summary of the plugin."""
    stops = []
    script('document.activeElement.blur(); window.scrollTo(0, 0);')
    for _ in range(400):
        press('\ue004')
        info = script('''
            const e = document.activeElement;
            if (!e || e === document.body) { return null; }
            if (!e.closest(arguments[0])) { return null; }
            if (e.dataset.ccSeen) { return 'cycle'; }
            e.dataset.ccSeen = '1';
            const st = getComputedStyle(e);
            const indicator = (st.outlineStyle !== 'none' && parseFloat(st.outlineWidth) > 0) ||
                (st.boxShadow !== '' && st.boxShadow !== 'none');
            const text = (e.innerText || e.getAttribute('aria-label') || '').trim().replace(/\\s+/g, ' ');
            return {tag: e.tagName.toLowerCase(), text: text.slice(0, 80), indicator: indicator,
                    open: e.tagName === 'SUMMARY' ? e.parentElement.open : null};''', [REGION])
        if info == 'cycle':
            break
        if info is None:
            continue
        if info['tag'] == 'summary':
            press('\ue007')
            time.sleep(0.2)
            info['openafterenter'] = script('return document.activeElement.parentElement.open;')
        stops.append(info)
    return {'stops': stops, 'count': len(stops), 'allindicated': all(s['indicator'] for s in stops),
            'summariesopen': all(s.get('openafterenter', True) for s in stops)}

def tree():
    """Every accessibility node whose DOM node is the block or the page region or inside it, in tree order."""
    root = cdp('DOM.getDocument', {'depth': 0})['root']['nodeId']
    found = cdp('DOM.querySelector', {'nodeId': root, 'selector': '.block_coursecare-overview'})['nodeId'] or \
        cdp('DOM.querySelector', {'nodeId': root, 'selector': 'section.block_coursecare'})['nodeId']
    ids = [found] + cdp('DOM.querySelectorAll', {'nodeId': found, 'selector': '*'})['nodeIds']
    inside = {cdp('DOM.describeNode', {'nodeId': i})['node']['backendNodeId'] for i in ids}
    nodes = cdp('Accessibility.getFullAXTree')['nodes']
    byid = {n['nodeId']: n for n in nodes}
    lines = []
    def walk(n, depth):
        role = n.get('role', {}).get('value', '')
        name = n.get('name', {}).get('value', '')
        props = {p['name']: p['value'].get('value') for p in n.get('properties', [])
                 if p['name'] in ('level', 'expanded')}
        domin = n.get('backendDOMNodeId') in inside
        shown = domin and not n.get('ignored') and role not in ('generic', 'none', 'InlineTextBox', 'LineBreak')
        if shown and not (role == 'StaticText' and lines and name and name in lines[-1]):
            extra = ''.join(f' [{k}={v}]' for k, v in props.items())
            lines.append('  ' * depth + f'{role}: {name}{extra}'.rstrip(': '))
        for c in n.get('childIds', []):
            if c in byid:
                walk(byid[c], depth + (1 if shown else 0))
    walk(nodes[0], 0)
    return {'tree': lines}

try:
    call('POST', base + '/window/rect', {'width': 1280, 'height': 900})
    go('/login/index.php')
    call('POST', base + f'/element/{find("#username")}/value', {'text': user})
    call('POST', base + f'/element/{find("#password")}/value', {'text': password})
    script('document.querySelector("#login").submit();')
    time.sleep(3)
    for item in items:
        check, rest = item.split(':', 1)
        name, path = rest.split('=', 1)
        cdp('Emulation.clearDeviceMetricsOverride')
        if check in ('zoom', 'narrow'):
            metrics(640 if check == 'zoom' else 320, 2)
        go(path)
        result = {'check': check, 'path': path, 'title': script('return document.title;')}
        if check == 'axe':
            result.update(axe())
        elif check == 'keys':
            result.update(keys())
        elif check == 'tree':
            result.update(tree())
        else:
            result.update(overflow())
            height = script('return document.documentElement.scrollHeight;')
            cdp('Emulation.setDeviceMetricsOverride', {'width': 640 if check == 'zoom' else 320,
                'height': min(int(height), 4000), 'deviceScaleFactor': 1, 'mobile': False})
            time.sleep(0.5)
            png = cdp('Page.captureScreenshot', {'format': 'png'})['data']
            with open(f'{outdir}/{name}.png', 'wb') as f:
                f.write(base64.b64decode(png))
        with open(f'{outdir}/{name}.json', 'w') as f:
            json.dump(result, f, ensure_ascii=False, indent=1)
        brief = {k: result[k] for k in ('pagescroll', 'count', 'allindicated', 'summariesopen') if k in result}
        if check == 'axe':
            brief = {'violations': [v['id'] for v in result.get('violations', [])], 'error': result.get('error')}
        print(name, '|', check, '|', brief)
finally:
    call('DELETE', base)
