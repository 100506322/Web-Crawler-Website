from datetime import datetime
import sys
from bs4 import BeautifulSoup
import requests
from concurrent.futures import ThreadPoolExecutor
import json

data = []

headers = ({
    'authority': 'www.amazon.co.uk',
    'cache-control': 'max-age=0',
    'rtt': '300',
    'downlink': '1.35',
    'ect': '3g',
    'sec-ch-ua': '"Google Chrome"; v="83"',
    'sec-ch-ua-mobile': '?0',
    'upgrade-insecure-requests': '1',
    'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36',
    'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    'sec-fetch-site': 'none',
    'sec-fetch-mode': 'navigate',
    'sec-fetch-user': '?1',
    'sec-fetch-dest': 'document',
    'accept-language': 'en-GB,en-US;q=0.9,en;q=0.8',
    'cookie': 'aws-priv=eyJ2IjoxLCJldSI6MCwic3QiOjB9; session-id=139-7350741-1081713; ubid-main=135-9894765-6184621; lc-main=en_UK; s_fid=0A4730DDD06B62E4-1DB478AB62143F35; regStatus=pre-register; x-main=hd2N9IEBuVL7il1dbkhEEHTQSf4Q7uviwjc2eikr0hRGGOyI2RYIiRsk3GvDKLSx; at-main=Atza|IwEBIJdoAZ4Y6j2IIGvC29t1ha634aK-p2kAl8rHhQRCSGMSU_nwQvM6fakAbYEjpVLPU4Jj0TwKvX70d6QnlouKPh0QwpHJG8rHUNVb-gmhS9shHM8fCJk45r1XW2FOSpLoM1iAO9kYIpOoW2M5We9xfdqlLuQBB-D5fQeO5Vqew4RnHesPNZuF4DQNlcqL7wrGjDY1JQKzlzARfATAuwaCy4jMD5bNmxpcWtTgNGrTtLpGv1Y-4Mnx2axxQYFgwpRNv_sPNZrMAfHdU7MX67HbyPyV3V21KAl8QNl0xE-lNl3myxnfyWH68Z5D-j501S7HWzkKxopy3SfGuwwZTjSVSVlnH4RmTwvEnW8W3tndcX6X1ETysYYXmO7TudIjtq7aUZqPBJe_MViePcWL3OV4q2b5; sess-at-main="TjcvTeXAA2dP6HOMGcG/n+Cdkr+peDBlNMOvfBz6oE0="; sst-main=Sst1|PQGR5AF9x4yS-iMft3B9aBzJC8v-e4M1kmB_3KS0pxtVTj1cH8hl3fajgigt6xEYhan-kUJuY5KNbteBgbiyDIRCs4ISve5MdRhDdoy7XKrVD1g5McZTyvdwYLfbTJbTUov51hOyPcE8BKpFL1bGpJiiJbZ0TV7Pyc6tkndogjneZATDErc4U08WE4LwPJxCiF-I-7Av4-JEfwH1ZQ81mz6rqy-K1o6bCMRRZ8kWuzrl0wobKsr4Sz0-m1K0waguIewhXNm4V4DLe8mn-_6I8_k9p9v3NiFRpp04v0Ptzw8V1ARo2U18t5f2nx54EXwHzvzOQlpeBVY2U0WpXDcKsU3C8Q; session-id-time=2082787201l; i18n-prefs=GBP; x-wl-uid=1MwJyD7dRnGiVdHw1PKiwmoNP9S/0xy+3KAKCJl2fM5VOthLzEW3dzyeW4zdKAepcIxkXpJFkxWcafUXXcS0MeSyLyFoBkl3xnNPLiRK0Rq33AHw0gL3W1FDBUn9OcakOzJGVGKZRc5E=; s_vn=1614974634531%26vn%3D4; s_nr=1590823888871-Repeat; s_vnum=2022823888872%26vn%3D1; s_dslv=1590823888874; sp-cdn="L5Z9:FR"; session-token=3AIPjoIrP8ITt1e/KXLZGSlnOPpirrWotNpCpCEfNRCY9mCfAV169URMcAX8XECtxt/qJujUn66Oyz8KIFDMieNmSdzEKA0K8I4AqbzplslzVGtZ6rNg+XsX/Bdc3hxnB7tUqQhrbrtVUncdzUMN1c95vhL7p+AEog3iiDkhLch0VO+Sl8HkAdZ/63xrp0stAaUsYo1GgsOFGI8+3wJUp4CHrJnoj/0lqjCJCpgXTZfxJcfWy9KarcGAPkno+fuMQqMoShJdi8R+DZ9XmIMib1bsLwXnerZa; csm-hit=tb:GVY0F2K4G05TXW59KB9M+s-GVY0F2K4G05TXW59KB9M|1592424615451&t:1592424615452&adb:adblk_yes',
}
)


def get_data(data, headers, page=1, keyword="Ipad"):
    url = f'https://www.amazon.co.uk/s?k={keyword}&page={page}'
    html = requests.get(url, headers=headers).text

    soup = BeautifulSoup(html, 'lxml')
    for item in soup.select('.s-result-item'):

        try:
            title = item.find("span", attrs={"class": 'a-size-medium a-color-base a-text-normal'})
            # Inner NavigableString Object
            title_value = title.string

            # Title as a string value
            title_string = title_value.strip()
        except AttributeError:
            try:
                title = item.find("span", attrs={"class": 'a-size-base-plus a-color-base a-text-normal'})
                # Inner NavigableString Object
                title_value = title.text
                # Title as a string value
                title_string = title_value.strip()
            except AttributeError:
                continue

        try:
            price = item.find("span", attrs={'class': 'a-offscreen'}).string.strip()

        except AttributeError:
            continue

        try:
            review_count = item.find("span", attrs={'class': 'a-size-base'}).string.strip()

        except AttributeError:
            review_count = "Reviews Unavailable"
        try:
            url = item.find('a', {'class': 'a-link-normal s-no-outline'}).get('href')
            url = 'https://www.amazon.co.uk/' + url

        except AttributeError:
            url = 'URL Not Available'
        # Appends each variable for that item in the wrapper into the data array
        data.append({
            'Product': {'Title': title_string, 'Price': price, 'NumberofProductReviews': review_count, 'Link': url},
        })
    return data


def multi_get_data(data, headers, start_page=1, end_page=20, workers=20):
    # Execute get_data in multiple threads each having a different page number
    with ThreadPoolExecutor(max_workers=workers) as executor:
        [executor.submit(get_data, data=data, headers=headers, page=i, keyword=sys.argv[1]) for i in range(start_page, end_page + 1)]
    return data


start_time = datetime.now()
k = multi_get_data(data, headers, start_page=1, end_page=8)
print(json.dumps(data))
#rint(json.dumps(data, indent = 2, ensure_ascii = False))
#print(f'Time take {datetime.now() - start_time}')
#print(f'Number of products returned {len(data)}')
