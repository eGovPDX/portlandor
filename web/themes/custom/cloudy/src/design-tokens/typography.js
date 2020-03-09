module.exports = {
  // QUESTION: How do we want to handle font wights?
  // QUESTION: Where should we set our grid-breakpoint map using these tokens?

  // Copied over Bootstrap's breakpoint values
  "breakpoint": {
    "xs": { "value": "0" },
    "sm": { "value": "576px" },
    "md": { "value": "768px" },
    "lg": { "value": "992px" },
    "xl": { "value": "1200px" }
  },
  "headerFontFamily": {
    "value": "'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif"
  },
  "prototypeFontFamily": {
    "value": "'Rubik', 'Helvetica Neue', Helvetica, Arial, sans-serif"
  },
  "heading": {
    "1": {
      "sm": {
        "size": { "value": "2.286rem" },
        "lineHeight": { "value": "1.25" }
      },
      "lg": {
        "size": { "value": "2.5rem" },
        "lineHeight": { "value": "1.3" }
      }
    },
    "2": {
      "sm": {
        "size": { "value": "1.714rem" },
        "lineHeight": { "value": "1.166666667" }
      },
      "lg": {
        "size": { "value": "2rem" },
        "lineHeight": { "value": "1.3125" }
      }
    },
    "3": {
      "sm": {
        "size": { "value": "1.286rem" },
        "lineHeight": { "value": "1.777777778" }
      },
      "lg": {
        "size": { "value": "1.5rem" },
        "lineHeight": { "value": "1.333333333" }
      }
    },
    "4": {
      "sm": {
        "size": { "value": "1.143rem" },
        "lineHeight": { "value": "1.5" }
      },
      "lg": {
        "size": { "value": "1.125rem" },
        "lineHeight": { "value": "1.333333333" }
      }
    },
    "5": {
      "sm": {
        "size": { "value": "1rem" },
        "lineHeight": { "value": "1.714285714" }
      },
      "lg": {
        "size": { "value": "1rem" },
        "lineHeight": { "value": "1.5" }
      }
    },
    "6": {
      "sm": {
        "size": { "value": "0.857rem" },
        "lineHeight": { "value": "2" }
      },
      "lg": {
        "size": { "value": "0.875rem" },
        "lineHeight": { "value": "1.714285714" }
      }
    }
  },
  "body": {
    "1": {
      "sm": {
        "size": { "value": "1rem", },
        "lineHeight": { "value": "1.428571429" }
      },
      "lg": {
        "size": { "value": "1rem", },
        "lineHeight": { "value": "1.5" }
      }
    },
    "2": {
      "sm": {
        "size": { "value": "0.857142857rem", },
        "lineHeight": { "value": "1.333333333" }
      },
      "lg": {
        "size": { "value": "0.875rem", },
        "lineHeight": { "value": "1.428571429" }
      }
    }
  }
  // TODO: Add Cloudy UI font style
}
