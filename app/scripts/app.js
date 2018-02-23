import { IoC } from 'scalar';
import { Message } from './scoop/Message';
import { Form } from './scoop/Form';

IoC.provide(Message, Form);
