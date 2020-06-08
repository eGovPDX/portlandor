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


# Generate a csv of code title and numeric order
page = request.urlopen('https://www.portlandoregon.gov/citycode/28148').read()
soup = BeautifulSoup(page, features="html.parser")
titles = {
    'number': [],
    'title': [],
    'name': [],
    'url': [],
}


chapter_regex = r"(?<=\.)[0-9]*"
count = 1

#Scrape the website to get the titles from the provided link
for title in soup.find_all('h2')[2:34]:
    url = title.find('a')['href'] #collects the url from all the h2's after the first 3 because they are not part of city code
    titles['url'].append('/citycode/' + url) #collects the url of the titles
    titles['name'].append(title.text) #collects the name of titles
    titles['number'].append(format(count, '02')) #add padding zero to make it two digit
    titles['title'].append(count)
    count += 1
# print('({})Titles printed'.format(len(titles['number'])))

#Saves Titles dictionary list to csv
titles = pd.DataFrame(titles)
titles.to_csv('city_code_titles.csv', index=False)

#Scrape the export made above for urls then it goes to each url and finds the appropriate fields for chapters
data = pd.read_csv('city_code_titles.csv')
chapters = {
    'id': [],
    'title': [],
    'name': [],
    'number': [],
    'documents': [],
    'url': [],
    'path_part': []
}

for link in data['url']:
    page = request.urlopen('https://www.portlandoregon.gov{}'.format(link)).read()
    soup = BeautifulSoup(page, features="html.parser")
    #Find the heading on each page and assign id, title, name, number, and url based on the patterns provided
    for heading in soup.find_all('h2')[1:]:
        count = 0
        name_trimmed = heading.text.strip()
        if not name_trimmed.startswith('Chapter'):
            # print('Skip: ', name_trimmed)
            continue
        chapter = re.findall(r"(?<=\.)[0-9]*", name_trimmed)[count]
        title = re.findall(r"[0-9]*[A-Z]?(?=\.)", name_trimmed)[count]
        name = name_trimmed
        #Make sure title of single digit has leading zero
        chapters['id'].append(title.zfill(2) + '-' + chapter)
        #When title starts with 14, it could be 14[ABCD], need to set the title to '14'
        if title.startswith('14'):
            chapters['title'].append('14')
            # Make chapter path as "A10"
            chapters['path_part'].append(title[-1:] + chapter)
            # Set alphanumeric sorting order
            chapters['number'].append(title[-1:] + '-' + chapter.zfill(3))
        else:
            chapters['title'].append(title)
            chapters['path_part'].append(chapter)
            #Make sure chapter number has leading zero
            chapters['number'].append(chapter.zfill(3))
        chapters['name'].append(name)
        chapters['documents'].append(' ')
        url = heading.find('a')['href']
        chapters['url'].append('/citycode/'+ url)
        count += 1
    # print('adding {} to chapters'.format(link))
# print('({}) Chapters Added'.format(len(chapters['number'])))
# Generate a csv Code Chapters
chapter = pd.DataFrame(chapters)
chapter.to_csv('city_code_chapters.csv', index=False)

data = pd.read_csv('city_code_chapters.csv')
data_raw = pd.read_csv('citycode_export.csv')
sections = {
    'id': [],
    'chapter_id': [],
    # 'Note': [],
    'number': [],
    'text': [],
    'name': [],
    'url': [],
}
# documents = []

number_regex = re.compile(r'^(\w+)\.(\d+)\.(\d+)(?:\s+|\.)')
document_count = 1

for link in data['url']:
    # print('URL: https://www.portlandoregon.gov{}'.format(link))
    page = request.urlopen('https://www.portlandoregon.gov{}'.format(link)).read()
    soup = BeautifulSoup(page, features="html.parser")
    # print(data['url'][0])
    print(data['name'])
    for heading in soup.find_all('h2')[2:]:
        try:
            #if the start of the a heading is -Notes it is not a chapter
            if heading.text.startswith('-'):
                # sections['Note'].append(heading.find('a')['href'])
                print(heading.text)
                continue
            #if the start of the heading is Figure it is not a sections
            elif heading.text.startswith('Figure'):
                # documents.append(heading.find('a')['href'])
                # print('Document added {}'.format(document_count))
                # document_count += 1
                continue
            else:
                results = re.findall(number_regex, heading.text)
                if(len(results)==0):
                  # print("ERROR:"+heading.text)
                  continue;
                title = results[0][0]
                chapter = results[0][1]
                section = results[0][2]
                title = title.zfill(2)
                section = section.zfill(3)
                id_number = str(title) + '-' + str(chapter) + '-' + str(section)
                # print(id_number)
                chapter_id = str(title) + '-' + str(chapter)
                sections['id'].append(id_number)
                sections['chapter_id'].append(chapter_id)
                # sections['Note'].append(' ')
                sections['number'].append(section)
                # sections['title'].append(title)
                sections['name'].append(heading.text)
                sections['text'].append(' ')
                url = heading.find('a')['href']
                sections['url'].append('/citycode/' + str(url))
        except IndexError:
            continue

    # print('loading {}'.format(id_number))



link_count = 1
data_sections = pd.read_csv('city_code_sections.csv')
data_raw_index = pd.Index(data_raw['URL'])
data_index = pd.Index(data_sections['url'])
#After finding the urls for all of the sections match those urls with the urls provided in the raw data export
#If the url matches replace the empty string with information in the TEXT field based on the index of the elements found
for x in data_sections['url']:
    for y in data_raw['URL']:
        sections_url = 'https://www.portlandoregon.gov' + x
        if sections_url == y:
            print('found {} at {}'.format(x, data_raw_index.get_loc('{}'.format(y))))
            sections['text'][data_index.get_loc(x)] = data_raw['TEXT'][data_raw_index.get_loc(y)]

section = pd.DataFrame(sections)
section.to_csv('city_code_sections.csv', index=False)


# print(len(sections['id']))
# print(len(sections['chapter']))
# print(len(sections['number']))
# print(len(sections['title']))
# print(len(sections['url']))
# print(len(sections['text']))