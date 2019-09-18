import { Module } from 'scalar';
import Messenger from './services/Messenger';
import Message from './components/message';
import Form from './components/form';

new Module(Messenger)
.compose('#msg', Message)
.compose('.scoop-form', Form);
