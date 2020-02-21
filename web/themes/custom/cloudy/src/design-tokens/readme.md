# Design Tokens

- Using [Style Dictionary](https://amzn.github.io/style-dictionary) format, look here for docs
- Compiles out to `./dist/_design-tokens.scss` so look there for how the output looks
- That in turn is pulled into the final `../cloudy.scss`

## Using tokens in tokens

See how the "inverse" color is using the default bg color by accessing it like you would in JS: `color.bg.default.value`. **Don't forget the `.value` at the end!**. [More info here](https://amzn.github.io/style-dictionary/#/properties?id=attribute-reference-alias)

```json
{
  "color": {
    "bg": {
      "default": {
        "value": "#ffffff"
      },
      "brand": {
        "value": "#16394b"
      }
    },
    "text": {
      "subdued": {
        "value": "#888888"
      },
      "inverse": {
        "value": "{color.bg.default.value}"
      }
    }
  }
}
```
