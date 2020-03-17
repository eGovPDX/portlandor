#!/usr/bin/env python3

# Install pandas and bs4 before running this script
# pip3 install pandas
# pip3 install bs4

# standard modules
import re

from datetime import datetime
from urllib import request, parse

# community modules
import pandas as pd
import numpy as np

from bs4 import BeautifulSoup

# Process the extract from the POG database
charter = pd.read_csv('city_charter_raw.csv')

charter['updated_date'] = charter.LAST_UPDATED_DATE.map(
    lambda x: datetime.strptime(x, '%Y-%m-%d %H:%M:%S %Z'))

chapter_regex = re.compile('Chapter (\d+) (.*)')
article_regex = re.compile('Article (\d+) (.*)')
section_regex = re.compile('[Section\s]*(\d+)-(\d+)[.]? (.*)')

charter['article_match'] = charter.CATEGORY_NAME.map(
    lambda x: article_regex.match(x))
charter['section_match'] = charter.CONTENT_NAME.map(
    lambda x: section_regex.match(x))

charter = charter[charter.article_match.notnull()].copy()
charter = charter[charter.section_match.notnull()].copy()

charter['article'] = charter.article_match.map(lambda x: x.group(1))
charter['chapter'] = charter.section_match.map(lambda x: x.group(1))
charter['number'] = charter.section_match.map(lambda x: x.group(2))
charter['name'] = charter.section_match.map(lambda x: x.group(3))
charter['title'] = charter.apply(lambda x: ' '.join(
    ['Section', '-'.join([x.chapter, x.number]), x['name']]), axis=1)
charter['article_id'] = charter.apply(
    lambda x: '-'.join([x.chapter, x.article]), axis=1)
charter['id'] = charter.apply(
    lambda x: '-'.join([x.chapter, x.number]), axis=1)
charter['URL'] = charter.URL.map(lambda x: parse.urlparse(x).path)

charter[['id', 'article_id', 'number', 'title', 'TEXT', 'URL']].to_csv(
    'city_charter_sections.csv', index=False)

# Scrape POG for chapters
page = request.urlopen('https://www.portlandoregon.gov/citycode/28149').read()
soup = BeautifulSoup(page, features='html.parser')
chapters = {
    'title': [],
    'name': [],
    'url': [],
    'note': []
}
# skip first h2 as it is 'City of Portland'
for heading in soup.find_all('h2')[1:]:
    chapters['title'].append(heading.get_text())
    chapters['name'].append(heading.next_sibling.next_sibling.get_text())
    chapters['url'].append('/citycode/' + heading.find('a').get('href'))
    for item in heading.parent.find('li'):
        if(item.get_text().startswith('-')):
            href = item.get('href')
            page = request.urlopen(
                'https://www.portlandoregon.gov/citycode/' + href).read()
            soup = BeautifulSoup(page, features='html.parser')
            chapters['note'].append(
                soup.find('article').find('section').get_text().strip())
        else:
            chapters['note'].append(np.nan)
chapters = pd.DataFrame(chapters)
chapters['number'] = chapters.title.map(
    lambda x: chapter_regex.match(x).group(1))
chapters['title'] = chapters.apply(lambda x: ' '.join(
    ['Chapter', str(x.number), str(x['name'])]), axis=1)
chapters[['number', 'title', 'url', 'note']].to_csv(
    'city_charter_chapters.csv', index=False)

# Scrape POG for articles
page = request.urlopen('https://www.portlandoregon.gov/citycode/28149').read()
soup = BeautifulSoup(page, features='html.parser')
articles = {
    'chapter': [],
    'title': [],
    'url': []
}
# skip first h2 as it is 'City of Portland'
for heading in soup.find_all('h2')[1:]:
    for item in heading.parent.find_all('li'):
        if(item.get_text().startswith('Article')):
            href = item.find('a').get('href')
            articles['chapter'].append(heading.get_text())
            articles['title'].append(item.get_text())
            articles['url'].append('/citycode/' + href)
articles = pd.DataFrame(articles)
articles['chapter'] = articles.chapter.map(
    lambda x: chapter_regex.match(x).group(1))
articles['number'] = articles.title.map(
    lambda x: article_regex.match(x).group(1))
articles['name'] = articles.title.map(
    lambda x: article_regex.match(x).group(2))

articles['title'] = articles.apply(lambda x: ' '.join(
    ['Article', str(x.number), str(x['name'])]), axis=1)
articles['id'] = articles.apply(
    lambda x: '-'.join([x.chapter, x.number]), axis=1)
articles[['id', 'chapter', 'number', 'title', 'url']].to_csv(
    'city_charter_articles.csv', index=False)
