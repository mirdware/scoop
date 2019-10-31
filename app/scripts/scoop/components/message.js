import Messenger from '../services/Messenger';

export default ($) => ({
  mount: () => $.inject(Messenger).component = $,
  '.close': {
    'click': () => $.type = 'not'
  }
});
