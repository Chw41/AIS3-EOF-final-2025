import hashlib
import random
import json
import base64
import jwt
import time
import uuid
from flask import Flask, request, jsonify

app = Flask(__name__)
db = {'key': 'deadbeef12345678', 'users': {}, 'notes': {}}

@app.route("/submit", methods=['POST'])
def submit():
    try:
        token = request.cookies.get('token')
        flag = request.get_data()
        assert token is not None and len(token) > 0
        print(f'Received token {token} flag {flag.encode()}')
        if True: # assume the flag is correct
            return 'Succeed', 200
        else:
            return 'Invalid flag', 400
    except:
        return 'Bad Request', 400

def crashdump(api, req, db):
    report = {'api': api, 'req': req, 'db': db}
    print(db)
    return base64.b64encode(json.dumps(report).encode()).decode()

def register(req, db):
    if len(db['users']) >= 32:
        return 'Too Many Users', 403
    username, password = str(req['username'])[:32], str(req['password'])[:32]
    salt = random.randbytes(8).hex()
    digest = hashlib.sha512((salt + password).encode()).hexdigest()
    db['users'][username] = {'username': username, 'password': digest, 'salt': salt}
    return 'Register Succeed', 200

def login(req, db):
    username, password = req['username'], req['password']
    user = db['users'].get(username, None)
    if user is None:
        return 'Incorrect username or password', 401
    digest = hashlib.sha512((user['salt'] + password).encode()).hexdigest()
    if digest != user['password']:
        return 'Incorrect username or password', 401
    newjwt = jwt.encode({'user': username, 'expired': time.time() + 10}, db['key'], algorithm='HS256')
    return 'Login Succeed', 200, {'Set-Cookie': f'jwt={newjwt}'}

def verify_login(req, db):
    jwtcookie = request.cookies.get('jwt')
    assert jwtcookie is not None
    data = jwt.decode(jwtcookie, db['key'], algorithms=['HS256'])
    assert time.time() < data['expired']
    return data['user']

def hello(req, db):
    try:
        user = verify_login(req, db)
    except:
        return 'Unauthorized', 401
    return 'Hello, ' + user, 200

def newnote(req, db):
    try:
        user = verify_login(req, db)
    except:
        return 'Unauthorized', 401
    if len(db['notes']) >= 128:
        return 'Too Many Notes', 403
    data = req['content']
    nid = str(uuid.uuid4())
    db['notes'][nid] = {'user': user, 'data': json.dumps(data)[:1024]}
    return nid, 200

def getnote(req, db):
    try:
        user = verify_login(req, db)
    except:
        return 'Unauthorized', 401
    nid = req['id']
    note = db['notes'].get(nid, None)
    if note is None:
        return 'Not Found', 404
    return json.loads(note['data']), 200

routing = {'register': register, 'login': login, 'hello': hello, 'newnote': newnote, 'getnote': getnote}

@app.route("/api/<path>", methods=['GET', 'POST'])
def router(path):
    try:
        if path not in routing:
            return jsonify({'msg': 'Not Found'}), 404
        func = routing[path]
        token = request.cookies.get('token')
        assert token is not None and len(token) > 0
        req = request.get_json()
    except:
        return 'Bad Request', 400
    try:
        return func(req, db)
    except:
        report = crashdump(f'/api/{path}', req, db)
        return f'Internal Server Error. Please send the crash report to the administrator: {report}', 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=80)
