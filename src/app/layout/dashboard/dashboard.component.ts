import { Component, OnInit, HostListener } from '@angular/core';
import { routerTransition } from '../../router.animations';

interface StatCard {
  bgClass: string;
  src: string;
  icon: string;
  label: string;
  urlId: string;
  content: string;
  isVisible: boolean;
}

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.scss'],
    animations: [routerTransition()]
})
export class DashboardComponent implements OnInit {
    public alerts: Array<any> = [];
    public sliders: Array<any> = [];
    public statCards: StatCard[] = [];
    public infoCardVisible = false;
    public scrollProgress = 0;

    constructor() {
        this.sliders.push(
            {
                imagePath: 'assets/images/slider1.jpg',
                label: 'First slide label',
                text: 'Nulla vitae elit libero, a pharetra augue mollis interdum.'
            },
            {
                imagePath: 'assets/images/slider2.jpg',
                label: 'Second slide label',
                text: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
            },
            {
                imagePath: 'assets/images/slider3.jpg',
                label: 'Third slide label',
                text: 'Praesent commodo cursus magna, vel scelerisque nisl consectetur.'
            },
            {
                imagePath: 'assets/images/slider4.jpg',
                label: 'Fourth slide label',
                text: 'Praesent commodo cursus magna, vel scelerisque nisl consectetur.'
            }
        );

        this.statCards = [
            {
                bgClass: 'primary',
                src: 'assets/images/products/high-frequency-transformer-250x250.jpg',
                icon: 'fa-comments',
                label: 'High Frequency Transformer',
                urlId: '1',
                content: 'With years of experience and expertise in this domain...',
                isVisible: false
            },
            {
                bgClass: 'warning',
                src: 'assets/images/products/cc-led-driver-250x250.jpg',
                icon: 'fa-tasks',
                label: 'Power SMPS',
                urlId: '8',
                content: 'SMPS-12V/1A to 12V/20A...',
                isVisible: false
            },
            {
                bgClass: 'success',
                src: 'assets/images/products/cc-led-driver-250x250.jpg',
                icon: 'fa-shopping-cart',
                label: 'CC LED Driver',
                urlId: '9',
                content: 'We are manufacturing & supplying a comprehensive range...',
                isVisible: false
            },
            {
                bgClass: 'danger',
                src: 'assets/images/products/electric-field-coils-250x250.jpg',
                icon: 'fa-support',
                label: 'Electric Field Coil',
                urlId: '10',
                content: 'We are offering a wide range of Electric Field Coil...',
                isVisible: false
            }
        ];

        this.alerts.push(
            {
                id: 1,
                type: 'success',
                message: `Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Voluptates est animi quibusdam praesentium quam, et perspiciatis,
                consectetur velit culpa molestias dignissimos
                voluptatum veritatis quod aliquam! Rerum placeat necessitatibus, vitae dolorum`
            },
            {
                id: 2,
                type: 'warning',
                message: `Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                Voluptates est animi quibusdam praesentium quam, et perspiciatis,
                consectetur velit culpa molestias dignissimos
                voluptatum veritatis quod aliquam! Rerum placeat necessitatibus, vitae dolorum`
            }
        );
    }

    ngOnInit() {
        this.observeCardVisibility();
    }

    private observeCardVisibility(): void {
        if (typeof IntersectionObserver !== 'undefined') {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const cardIndex = parseInt(entry.target.getAttribute('data-card-index') || '0', 10);
                        if (cardIndex < this.statCards.length) {
                            this.statCards[cardIndex].isVisible = true;
                        }
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -80px 0px'
            });

            const infoObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.infoCardVisible = true;
                        infoObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.2,
                rootMargin: '0px 0px -100px 0px'
            });

            setTimeout(() => {
                document.querySelectorAll('[data-card-index]').forEach(el => {
                    observer.observe(el);
                });
                const infoCard = document.querySelector('.dashboard-info-card-wrapper');
                if (infoCard) {
                    infoObserver.observe(infoCard);
                }
            }, 100);
        }
    }

    @HostListener('window:scroll', ['$event'])
    onWindowScroll(): void {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        this.scrollProgress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    }

    public closeAlert(alert: any) {
        const index: number = this.alerts.indexOf(alert);
        this.alerts.splice(index, 1);
    }
}

