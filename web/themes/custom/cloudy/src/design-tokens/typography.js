module.exports = {
  "fontFamily": {
    "display": {
      "value": "'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif"
    }
  },
  "type": {
    "base": {
      "fontSize": { "value": "16px" }
    },
    "heading-01": {
      "fontSize": { "value": "28px" },
      "lineHeight": { "value": "34px" },
      "md": {
        "fontSize": { "value": "48px" },
        "lineHeight": { "value": "56px" }
      }
    },
    "heading-02": {
      "fontSize": { "value": "24px" },
      "lineHeight": { "value": "30px" },
      "md": {
        "fontSize": { "value": "40px" },
        "lineHeight": { "value": "48px" }
      }
    },
    "heading-03": {
      "fontSize": { "value": "22px" },
      "lineHeight": { "value": "28px" },
      "md": {
        "fontSize": { "value": "32px" },
        "lineHeight": { "value": "40px" }
      }
    },
    "heading-04": {
      "fontSize": { "value": "20px" },
      "lineHeight": { "value": "26px" },
      "md": {
        "fontSize": { "value": "24px" },
        "lineHeight": { "value": "32px" }
      }
    },
    "heading-05": {
      "fontSize": { "value": "18px" },
      "lineHeight": { "value": "24px" },
      "md": {
        "fontSize": { "value": "20px" },
        "lineHeight": { "value": "24px" }
      }
    },
    "heading-06": {
      "fontSize": { "value": "16px" },
      "lineHeight": { "value": "24px" },
    },
    "body": {
      "fontSize": { "value": "{ type.base.fontSize.value }" },
      "lineHeight": { "value": "20px" },
      "md": {
        "lineHeight": { "value": "24px" }
      }
    }
  },
  "lead": {
    "fontSize": { "value": "18px" },
    "lineHeight": { "value": "24px" },
    "md": {
      "fontSize": { "value": "20px" },
      "lineHeight": { "value": "28px" }
    }
  },
  "subtitle": {
    "fontSize": { "value": "16px" },
    "lineHeight": { "value": "24px" },
  },
  "link-text": {
    "fontSize": { "value": "{ type.body.fontSize.value }" },
    "lineHeight": { "value": "{ type.body.lineHeight.value }" },
    "md": {
      "lineHeight": { "value": "{ type.body.md.lineHeight.value }" }
    }
  },
  "helper-text": {
    "fontSize": { "value": "14px" },
    "lineHeight": { "value": "16px" },
    "md": {
      "lineHeight": { "value": "24px" }
    }
  },
  "ui-text": {
    "fontSize": { "value": "{ type.body.fontSize.value }" },
    "lineHeight": { "value": "22px" },
  },
  "permalink-text": {
    "fontSize": { "value": "{ type.body.fontSize.value }" },
    "lineHeight": { "value": "24px" },
  },
  "time-text": {
    "fontSize": { "value": "{ type.body.fontSize.value }" },
    "lineHeight": { "value": "24px" },
    "md": {
      "fontSize": { "value": "20px" }
    }
  }
}
