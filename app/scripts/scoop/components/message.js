import { inject } from 'scalar';
import Messenger from '../services/Messenger';

export default inject(Messenger)(($, messenger) => ({
  mount: () => messenger.component = $,
  '.close': {
    click: () => $.type = 'not'
  }
}));
