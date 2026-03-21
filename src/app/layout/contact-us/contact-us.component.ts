import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-contact-us',
  templateUrl: './contact-us.component.html',
  styleUrls: ['./contact-us.component.scss']
})
export class ContactUsComponent implements OnInit {
  captcha: string = "";
  userCaptcha: string = "";
  invalidCaptCha: boolean = false;
  form = {
    fullname: '',
    phone: '',
    email: '',
    enquiry: '',
  };
  constructor() { }

  ngOnInit(): void {
  }
}
