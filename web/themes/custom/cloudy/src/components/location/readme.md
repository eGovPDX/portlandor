
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
    address: 'SW Macadam Avenue and Nebraska Street',
    address2: '6500 SW Macadam Ave, Portland, OR 97219',
    hours: '5:00am-10:00pm. Closed to vehicles at 10:00pm.',
    links: [
      {
        icon: {
          name: 'map-pin',
        },
        text: 'Picnic Site Maps & Info',
        url: 'parks/search/1234',
      },
      {
        icon: {
          name: 'information',
        },
        text: 'The 2015 Willamette Park Redevelopment Project',
        url: '2015/park-project',
      },
      {
        icon: {
          name: 'email',
        },
        text: 'Email Parks Redevelopment Team',
        url: '#email-link',
      },
    ]
} only %}

```
