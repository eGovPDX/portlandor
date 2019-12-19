# Search Result

## Props

- `bundle`*: string - the "content type" bundle that this search results belongs to, ie. `News`, `Event`, etc
- `title`*: string - the main title of the content being linked out to
- `url`*: string - the url for the node of content
- `posted_on`: string - A date that the node was posted on
- `date_start`: string - A start date for the event. Can be used without an end date
- `date_end`: string - An end date for the event. Requires the inclusion of a start date
- `is_not_published`: boolean - If set to true, search result gets a "Unpublished" danger badge
  
## Usage

```twig
{% include "@components/search-result/search-result.twig" with {
  bundle: "News ",
  title: "Have you recieved a City of Portland notice in the mail about your single-family home?",
  url: 'https://www.google.com',
  posted_on: 'April 2, 2018',
  text: "Here's what it means and what you can do."
} %}
```
