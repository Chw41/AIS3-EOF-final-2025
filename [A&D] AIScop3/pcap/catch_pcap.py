import os
import requests
import time

url_template = 'http://35.221.181.38:8888/pcap/3/{}'
headers = {
    'accept': 'application/json',
    'Authorization': '6b7ee05971955ed5359e93f53e46a999'
}
save_path = '/Users/CWei/Desktop/CyberSec/AIS3 EOF/2025/final/pcap'

if not os.path.exists(save_path):
    os.makedirs(save_path)

already_downloaded = set(os.listdir(save_path))

for round_number in range(90, 180):
    file_name = f'{round_number}.pcap'
    
    if file_name in already_downloaded:
        continue
    
    url = url_template.format(round_number)
    
    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:
        with open(os.path.join(save_path, file_name), 'wb') as f:
            f.write(response.content)
        print(f'Catch Round: {round_number} Success')
    else:
        print(f'Round: {round_number} Fail，Status：{response.status_code}')
    
    time.sleep(3)
