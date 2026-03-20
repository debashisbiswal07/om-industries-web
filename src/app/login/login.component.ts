import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { routerTransition } from '../router.animations';
import { AuthService } from '../shared/services/auth.service';
import { ToastrService } from 'ngx-toastr';

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.scss'],
    animations: [routerTransition()]
})
export class LoginComponent implements OnInit {
    credentials = { username: '', password: '' };
    form = {
        email: '',
        password: ''
    };

    constructor(private authService: AuthService, private router: Router, private toastrService: ToastrService) { }

    ngOnInit() { }

    onLoggedin() {
        //localStorage.setItem('isLoggedin', 'true');
        //console.log(this.form);
        if (this.form && this.form.email && this.form.password) {
            this.credentials.username = this.form.email;
            this.credentials.password = this.form.password;
            this.authService.login(this.credentials).subscribe(
                (response) => {
                    this.authService.saveToken(response.token);
                    this.router.navigate(['/dashboard']);
                },
                (error) => {
                    console.error('Login failed', error);
                    this.toastrService.error('Message Error!', 'Login failed');
                    // Handle error (e.g., display error message)
                }
            );
        }
    }

    onClose() {
        this.router.navigate(['/dashboard']);
    }

    onSubmit(event: Event) {
        this.router.navigate(['/dashboard']);
    }
}
