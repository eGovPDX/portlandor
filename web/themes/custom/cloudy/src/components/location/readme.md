
**Location** is a component that holds data related to a specific place (e.g. park facility, office, or conference room), it also contains the **Link** component.

## Props

- `placename`: string - Display name of the location
- `address`: string - The address of the location
- `address2`: string - The suite name, room number, etc
- `hours`: string - Hours of operation
- `links`: array[ object{icon: object{name: string, size: string - default 'xs'}, text: string, url: string} ]

## Usage

```twig
{% include "@components/location/location.twig" with {
  placename: 'Willamette Park',
  address: '6500 SW Macadam Ave, Portland, OR 97219',
  links: [
    {
      icon: {
        name: 'map-pin',
      },
      text: 'Get Directions',
      url: '/get-directions',
    },
    {
      icon: {
        name: 'information',
      },
      text: 'More about this location',
      url: '/more-about-location',
    },
  ]
} only %}
```
