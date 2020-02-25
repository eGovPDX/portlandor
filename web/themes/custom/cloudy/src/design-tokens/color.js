module.exports = {
  color: {
    bg: {
      neutral: {
        '800': { value: '#00050A' },
        '500': { value: '#667E99' },
        '300': { value: '#AFBECF' },
        '100': { value: '#EEF2F6' },
        '0': {
          value: '#FFFFFF',
          comment: 'White',
        },
      },
      blue: {
        '800': { value: '#174069' },
        '500': { value: '#1F78D1' },
        '300': { value: '#8CB3D9' },
        '100': { value: '#E9F2FC' },
      },
      green: {
        '800': { value: '#206035' },
        '500': { value: '#339933' },
        '300': { value: '#94D194' },
        '100': { value: '#EFF5EF' },
      },
      yellow: {
        '800': { value: '#B38600' },
        '500': { value: '#F2B90D' },
        '300': { value: '#FCDE83' },
        '100': { value: '#FEF9E7' },
      },
      orange: {
        '800': { value: '#793606' },
        '500': { value: '#E66F1A' },
        '300': { value: '#ECA979' },
        '100': { value: '#F9F1EC' },
      },
      red: {
        '800': { value: '#66191A' },
        '500': { value: '#CC3333' },
        '300': { value: '#CC6677' },
        '100': { value: '#F9ECEC' },
      },
      purple: {
        '800': { value: '#202060' },
        '500': { value: '#4040BF' },
        '300': { value: '#8C8CD9' },
        '100': { value: '#EFEFF5' },
      },
      teal: {
        '800': { value: '#206060' },
        '500': { value: '#22A0A0' },
        '300': { value: '#8CD9D9' },
        '100': { value: '#EDF7F7' },
      },
    },
    text: {
      dark: { value: '{color.bg.neutral.800.value}' },
      light: { value: '{color.bg.neutral.0.value}' },
    },
  },
};
