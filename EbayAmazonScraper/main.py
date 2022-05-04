from collections import defaultdict
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
import lxml
import sys
from bs4 import BeautifulSoup
import json
import requests
from concurrent.futures import ThreadPoolExecutor


data = []

headers = ({
    'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36',
    'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    'accept-language': 'en-GB,en-US;q=0.9,en;q=0.8',
}
)


def get_data(data, headers, page=1, keyword="Ipad"):
    url = f'https://www.ebay.co.uk/sch/i.html?_from=R40&_trksid=p2334524.m570.l1313&_nkw={keyword}&_pgn={page}'
    html = requests.get(url, headers=headers).text

    soup = BeautifulSoup(html, 'lxml')
    # Loop through each item in wrapper
    for item in soup.select('.s-item__wrapper.clearfix'):
        # Saves each respective information as relevant variables as a string
        # using html class names on eBay's search page
        try:
            title = item.select_one('.s-item__title').text
        except:
            title = "Title Unavailable"
        try:
            link = item.select_one('.s-item__link')['href']
            if link[0:22] != "https://www.ebay.co.uk":
                link = "Link unavailable"
        except:
            link = "Link Unavailable"

        try:
            reviews = item.select_one('.s-item__reviews-count span').text.split(' ')[0]
        except:
            reviews = "Reviews Unavailable"

        try:
            price = item.select_one('.s-item__price').text
        except:
            price = "Price Unavailable"

        if link == "Link Unavailable":
            continue
        else:
            # Appends each variable for that item in the wrapper into the data array
            data.append({
                'item': {'title': title, 'link': link, 'price': price},
                'reviews': reviews
            })
    return data


def multi_get_data(data, headers, start_page=1, end_page=20, workers=20):
    # Execute get_data in multiple threads each having a different page number
    with ThreadPoolExecutor(max_workers=workers) as executor:
        [executor.submit(get_data, data=data, headers=headers, page=i, keyword=sys.argv[1]) for i in range(start_page, end_page + 1)]
    return data

start_time = datetime.now()
k = multi_get_data(data, headers, start_page=1, end_page=2)
#print(json.dumps(data, indent = 2, ensure_ascii = False))
print(json.dumps(data))
#print(f'Time take {datetime.now() - start_time}')
#print(f'Number of products returned {len(data)}')
