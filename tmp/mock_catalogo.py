from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from urllib.parse import parse_qs
class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args): pass
    def do_GET(self):
        if self.path == '/login':
            code, body = 200, 'const name="csrf_test_name",token="abcdef1234"'
        elif 'session=valid' in self.headers.get('Cookie',''):
            code, body = 200, '{"success":true,"total_records":100,"productos":[{"pla_id":1}]}'
        else:
            code, body = 302, ''
        self.send_response(code); self.end_headers(); self.wfile.write(body.encode())
    def do_POST(self):
        values = parse_qs(self.rfile.read(int(self.headers['Content-Length'])).decode())
        valid = values.get('csrf_test_name') == ['abcdef1234'] and values.get('password') == ['dummy']
        self.send_response(302 if valid else 403)
        if valid: self.send_header('Set-Cookie','session=valid; Path=/')
        self.end_headers()
ThreadingHTTPServer(('127.0.0.1',18764), Handler).serve_forever()
